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
import json
import logging
from contextlib import asynccontextmanager
from typing import Optional
    
import numpy as np
import torch
from facenet_pytorch import MTCNN as FNMTCNN, InceptionResnetV1
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from PIL import Image
from pydantic import BaseModel

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Global model instances (loaded once at startup).
mtcnn_model: Optional[FNMTCNN] = None
facenet_model: Optional[InceptionResnetV1] = None

# Threshold cosine similarity (Nusantoko & Prapanca, 2025).
SIMILARITY_THRESHOLD = 0.7
EMBEDDING_DIMENSION = 512


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load MTCNN dan FaceNet model saat startup."""
    global mtcnn_model, facenet_model

    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    logger.info(f"Loading models on device: {device}")

    # MTCNN untuk deteksi wajah + crop.
    try:
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
    finally:
        mtcnn_model = None
        facenet_model = None
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
    """Load image dari bytes, konversi ke RGB, resize ke maks 320px."""
    img = Image.open(io.BytesIO(file_bytes))
    img.load()
    if img.mode != "RGB":
        img = img.convert("RGB")
    # Resize ke maks 320px supaya MTCNN + FaceNet lebih cepat.
    # FaceNet hanya butuh 160x160 → input 320px sudah lebih dari cukup.
    max_size = 320
    if max(img.size) > max_size:
        ratio = max_size / max(img.size)
        new_size = (int(img.size[0] * ratio), int(img.size[1] * ratio))
        img = img.resize(new_size, Image.LANCZOS)
    return img


def models_ready() -> bool:
    """Return whether both models needed by the embedding pipeline are loaded."""
    return mtcnn_model is not None and facenet_model is not None


def validate_stored_embedding(
    value: object,
    expected_dimension: int = EMBEDDING_DIMENSION,
) -> np.ndarray:
    """Validate and return one finite, non-zero numeric embedding vector."""
    try:
        embedding = np.asarray(value)
    except (TypeError, ValueError) as exc:
        raise ValueError("stored_embedding harus berupa array numerik.") from exc

    if embedding.ndim != 1 or embedding.shape[0] != expected_dimension:
        raise ValueError(
            f"stored_embedding harus berupa array {expected_dimension} dimensi."
        )
    if embedding.dtype.kind not in "iuf":
        raise ValueError("stored_embedding harus berupa array numerik.")

    embedding = embedding.astype(np.float32, copy=False)
    if not np.isfinite(embedding).all():
        raise ValueError("stored_embedding harus berisi nilai finite.")
    if np.linalg.norm(embedding) == 0:
        raise ValueError("stored_embedding tidak boleh berupa vektor nol.")
    return embedding


def cosine_similarity(reference: np.ndarray, candidate: np.ndarray) -> float:
    """Calculate cosine similarity for finite, same-shaped, non-zero vectors."""
    reference = np.asarray(reference, dtype=np.float64)
    candidate = np.asarray(candidate, dtype=np.float64)
    if reference.ndim != 1 or candidate.ndim != 1 or reference.shape != candidate.shape:
        raise ValueError("Embedding harus berupa vektor dengan dimensi yang sama.")
    if not np.isfinite(reference).all() or not np.isfinite(candidate).all():
        raise ValueError("Embedding harus berisi nilai finite.")

    denominator = np.linalg.norm(reference) * np.linalg.norm(candidate)
    if denominator == 0:
        raise ValueError("Embedding tidak boleh berupa vektor nol.")
    return float(np.dot(reference, candidate) / denominator)


def get_embedding(face_img: Image.Image) -> Optional[np.ndarray]:
    """Generate 512-dimensi embedding dari gambar wajah yang sudah di-crop."""
    global mtcnn_model, facenet_model
    if not models_ready():
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
        models_loaded=models_ready(),
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
    except Exception:
        logger.exception("Detect error")
        raise HTTPException(status_code=500, detail="Gagal memproses deteksi wajah.")


@app.post("/embed", response_model=EmbedResponse)
async def embed(file: UploadFile = File(...)):
    """
    Generate face embedding dari gambar wajah.

    Gambar harus berisi 1 wajah yang jelas. Mengembalikan vektor
    512-dimensi yang bisa disimpan di database untuk verifikasi nanti.
    """
    if not models_ready():
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
    except Exception:
        logger.exception("Embed error")
        raise HTTPException(status_code=500, detail="Gagal membuat embedding wajah.")


@app.post("/verify", response_model=VerifyResponse)
async def verify(
    file: UploadFile = File(...),
    stored_embedding: str = Form(...),
):
    """
    Verifikasi wajah: bandingkan gambar baru dengan embedding tersimpan.

    - `file`: gambar wajah baru (dari kamera saat absensi)
    - `stored_embedding`: JSON string vektor embedding yang tersimpan di DB

    Mengembalikan `match=true` jika cosine similarity ≥ 0.7.
    """
    if not models_ready():
        raise HTTPException(status_code=503, detail="Model belum dimuat.")

    try:
        stored = validate_stored_embedding(json.loads(stored_embedding))
    except json.JSONDecodeError:
        raise HTTPException(status_code=400, detail="stored_embedding bukan JSON valid.")
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc))

    try:
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
        similarity = cosine_similarity(stored, new_embedding)
        match = similarity >= SIMILARITY_THRESHOLD

        return VerifyResponse(
            match=match,
            similarity=round(float(similarity), 4),
            threshold=SIMILARITY_THRESHOLD,
            message="Wajah cocok." if match else "Wajah tidak cocok.",
        )
    except Exception:
        logger.exception("Verify error")
        raise HTTPException(status_code=500, detail="Gagal memverifikasi wajah.")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
