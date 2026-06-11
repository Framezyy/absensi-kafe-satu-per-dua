"""
Microservice FastAPI untuk Face Recognition.

Endpoint:
- POST /detect  — Deteksi wajah dalam gambar (MTCNN)
- POST /embed   — Generate face embedding (FaceNet)
- POST /verify  — Verifikasi 2 embedding (cosine similarity)
- GET  /health  — Health check

Teknologi:
- MTCNN: Multi-task Cascaded Convolutional Networks untuk deteksi wajah
- FaceNet: Model deep learning untuk mengonversi wajah menjadi vektor 512-dimensi
- Cosine similarity: perbandingan 2 vektor embedding (threshold ≥ 0.7)

Referensi:
- Nusantoko & Prapanca (2025): threshold 70% menghasilkan akurasi tertinggi
"""

import io
import logging
from contextlib import asynccontextmanager
from typing import Optional
    
import numpy as np
import torch
from facenet_pytorch import MTCNN as FNMTCNN, InceptionResnetV1
from fastapi import FastAPI, File, HTTPException, UploadFile
from PIL import Image
from pydantic import BaseModel
from scipy.spatial.distance import cosine

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Global model instances (loaded once at startup).
mtcnn_model: Optional[FNMTCNN] = None
facenet_model: Optional[InceptionResnetV1] = None

# Threshold cosine similarity (Nusantoko & Prapanca, 2025).
SIMILARITY_THRESHOLD = 0.7


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load MTCNN dan FaceNet model saat startup."""
    global mtcnn_model, facenet_model

    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    logger.info(f"Loading models on device: {device}")

    # MTCNN untuk deteksi wajah + crop.
    mtcnn_model = FNMTCNN(
        image_size=160,
        margin=0,
        min_face_size=20,
        thresholds=[0.6, 0.7, 0.7],
        factor=0.709,
        post_process=True,
        device=device,
    )

    # FaceNet (InceptionResnetV1 pretrained pada VGGFace2) untuk embedding.
    facenet_model = InceptionResnetV1(pretrained="vggface2").eval().to(device)

    logger.info("Models loaded successfully.")
    yield
    logger.info("Shutting down.")


app = FastAPI(
    title="Kafe Satu Per Dua — Face Recognition Service",
    description="Microservice untuk deteksi wajah (MTCNN), face embedding (FaceNet), "
    "dan verifikasi identitas (cosine similarity).",
    version="1.0.0",
    lifespan=lifespan,
)


# ─── Response Models ───

class DetectResponse(BaseModel):
    face_detected: bool
    num_faces: int
    boxes: list[list[float]]  # [[x1, y1, x2, y2], ...]


class EmbedResponse(BaseModel):
    success: bool
    embedding: Optional[list[float]] = None
    message: Optional[str] = None


class VerifyResponse(BaseModel):
    match: bool
    similarity: float
    threshold: float
    message: str


class HealthResponse(BaseModel):
    status: str
    device: str
    models_loaded: bool


# ─── Helper Functions ───

def load_image(file_bytes: bytes) -> Image.Image:
    """Load image dari bytes dan konversi ke RGB."""
    img = Image.open(io.BytesIO(file_bytes))
    if img.mode != "RGB":
        img = img.convert("RGB")
    return img


def get_embedding(face_img: Image.Image) -> Optional[np.ndarray]:
    """Generate 512-dimensi embedding dari gambar wajah yang sudah di-crop."""
    global mtcnn_model, facenet_model
    if mtcnn_model is None or facenet_model is None:
        return None

    device = next(facenet_model.parameters()).device

    # MTCNN crop + align wajah.
    face_tensor = mtcnn_model(face_img)
    if face_tensor is None:
        return None

    # Tambah batch dimension dan pindah ke device.
    face_tensor = face_tensor.unsqueeze(0).to(device)

    # Generate embedding.
    with torch.no_grad():
        embedding = facenet_model(face_tensor)

    return embedding.squeeze().cpu().numpy()


# ─── Endpoints ───

@app.get("/health", response_model=HealthResponse)
async def health():
    """Health check endpoint."""
    device = "cuda" if torch.cuda.is_available() else "cpu"
    return HealthResponse(
        status="ok",
        device=device,
        models_loaded=mtcnn_model is not None and facenet_model is not None,
    )


@app.post("/detect", response_model=DetectResponse)
async def detect(file: UploadFile = File(...)):
    """
    Deteksi wajah dalam gambar menggunakan MTCNN.

    Mengembalikan bounding box untuk setiap wajah yang terdeteksi.
    """
    if mtcnn_model is None:
        raise HTTPException(status_code=503, detail="Model belum dimuat.")

    try:
        img = load_image(await file.read())
        boxes, _ = mtcnn_model.detect(img)

        if boxes is None:
            return DetectResponse(face_detected=False, num_faces=0, boxes=[])

        return DetectResponse(
            face_detected=True,
            num_faces=len(boxes),
            boxes=boxes.tolist(),
        )
    except Exception as e:
        logger.error(f"Detect error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/embed", response_model=EmbedResponse)
async def embed(file: UploadFile = File(...)):
    """
    Generate face embedding dari gambar wajah.

    Gambar harus berisi 1 wajah yang jelas. Mengembalikan vektor
    512-dimensi yang bisa disimpan di database untuk verifikasi nanti.
    """
    if facenet_model is None:
        raise HTTPException(status_code=503, detail="Model belum dimuat.")

    try:
        img = load_image(await file.read())
        embedding = get_embedding(img)

        if embedding is None:
            return EmbedResponse(
                success=False,
                message="Wajah tidak terdeteksi dalam gambar.",
            )

        return EmbedResponse(
            success=True,
            embedding=embedding.tolist(),
        )
    except Exception as e:
        logger.error(f"Embed error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/verify", response_model=VerifyResponse)
async def verify(
    file: UploadFile = File(...),
    stored_embedding: str = "",
):
    """
    Verifikasi wajah: bandingkan gambar baru dengan embedding tersimpan.

    - `file`: gambar wajah baru (dari kamera saat absensi)
    - `stored_embedding`: JSON string vektor embedding yang tersimpan di DB

    Mengembalikan `match=true` jika cosine similarity ≥ 0.7.
    """
    if facenet_model is None:
        raise HTTPException(status_code=503, detail="Model belum dimuat.")

    try:
        # Parse stored embedding.
        import json
        stored = np.array(json.loads(stored_embedding), dtype=np.float32)

        # Generate embedding dari gambar baru.
        img = load_image(await file.read())
        new_embedding = get_embedding(img)

        if new_embedding is None:
            return VerifyResponse(
                match=False,
                similarity=0.0,
                threshold=SIMILARITY_THRESHOLD,
                message="Wajah tidak terdeteksi dalam gambar.",
            )

        # Hitung cosine similarity.
        similarity = 1 - cosine(stored, new_embedding)
        match = similarity >= SIMILARITY_THRESHOLD

        return VerifyResponse(
            match=match,
            similarity=round(float(similarity), 4),
            threshold=SIMILARITY_THRESHOLD,
            message="Wajah cocok." if match else "Wajah tidak cocok.",
        )
    except json.JSONDecodeError:
        raise HTTPException(status_code=400, detail="stored_embedding bukan JSON valid.")
    except Exception as e:
        logger.error(f"Verify error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
