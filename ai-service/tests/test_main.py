import io
import json
import unittest
from unittest.mock import MagicMock, patch

import numpy as np
from fastapi import HTTPException
from PIL import Image, UnidentifiedImageError

import main


class FakeUploadFile:
    def __init__(self, content=b"image"):
        self.content = content

    async def read(self):
        return self.content


def unit_vector(index=0):
    vector = np.zeros(main.EMBEDDING_DIMENSION, dtype=np.float32)
    vector[index] = 1.0
    return vector


class ModelStateMixin:
    def setUp(self):
        self.original_mtcnn = main.mtcnn_model
        self.original_facenet = main.facenet_model
        main.mtcnn_model = MagicMock()
        main.facenet_model = MagicMock()

    def tearDown(self):
        main.mtcnn_model = self.original_mtcnn
        main.facenet_model = self.original_facenet


class HelperTests(unittest.TestCase):
    def test_load_image_converts_to_rgb(self):
        source = Image.new("L", (20, 10))
        payload = io.BytesIO()
        source.save(payload, format="PNG")

        result = main.load_image(payload.getvalue())

        self.assertEqual(result.mode, "RGB")
        self.assertEqual(result.size, (20, 10))

    def test_load_image_resizes_preserving_aspect_ratio(self):
        source = Image.new("RGB", (640, 160))
        payload = io.BytesIO()
        source.save(payload, format="PNG")

        result = main.load_image(payload.getvalue())

        self.assertEqual(result.size, (320, 80))

    def test_load_image_rejects_corrupt_data(self):
        with self.assertRaises(UnidentifiedImageError):
            main.load_image(b"not an image")

    def test_validate_stored_embedding_accepts_valid_vector(self):
        result = main.validate_stored_embedding(unit_vector().tolist())
        self.assertEqual(result.shape, (main.EMBEDDING_DIMENSION,))
        self.assertEqual(result.dtype, np.float32)

    def test_validate_stored_embedding_rejects_invalid_values(self):
        invalid_values = [
            [1.0],
            [[1.0] * main.EMBEDDING_DIMENSION],
            [0.0] * main.EMBEDDING_DIMENSION,
            [float("nan")] + [0.0] * (main.EMBEDDING_DIMENSION - 1),
            ["1"] * main.EMBEDDING_DIMENSION,
        ]
        for value in invalid_values:
            with self.subTest(value_type=type(value[0]).__name__):
                with self.assertRaises(ValueError):
                    main.validate_stored_embedding(value)

    def test_cosine_similarity_identical_and_orthogonal(self):
        self.assertAlmostEqual(main.cosine_similarity(unit_vector(0), unit_vector(0)), 1.0)
        self.assertAlmostEqual(main.cosine_similarity(unit_vector(0), unit_vector(1)), 0.0)


class EndpointTests(ModelStateMixin, unittest.IsolatedAsyncioTestCase):
    async def test_health_reports_models_loaded_and_unloaded(self):
        loaded = await main.health()
        self.assertTrue(loaded.models_loaded)

        main.mtcnn_model = None
        unloaded = await main.health()
        self.assertFalse(unloaded.models_loaded)

    async def test_detect_model_unavailable(self):
        main.mtcnn_model = None
        with self.assertRaises(HTTPException) as context:
            await main.detect(FakeUploadFile())
        self.assertEqual(context.exception.status_code, 503)

    async def test_detect_no_face(self):
        main.mtcnn_model.detect.return_value = (None, None)
        with patch.object(main, "load_image", return_value=MagicMock()):
            result = await main.detect(FakeUploadFile())
        self.assertFalse(result.face_detected)
        self.assertEqual(result.num_faces, 0)
        self.assertEqual(result.boxes, [])

    async def test_detect_returns_boxes(self):
        boxes = np.array([[1.0, 2.0, 3.0, 4.0], [5.0, 6.0, 7.0, 8.0]])
        main.mtcnn_model.detect.return_value = (boxes, np.array([0.9, 0.8]))
        with patch.object(main, "load_image", return_value=MagicMock()):
            result = await main.detect(FakeUploadFile())
        self.assertTrue(result.face_detected)
        self.assertEqual(result.num_faces, 2)
        self.assertEqual(result.boxes, boxes.tolist())

    async def test_embed_requires_both_models(self):
        for missing_model in ("mtcnn_model", "facenet_model"):
            with self.subTest(missing_model=missing_model):
                original = getattr(main, missing_model)
                setattr(main, missing_model, None)
                with self.assertRaises(HTTPException) as context:
                    await main.embed(FakeUploadFile())
                self.assertEqual(context.exception.status_code, 503)
                setattr(main, missing_model, original)

    async def test_embed_no_face(self):
        with patch.object(main, "load_image", return_value=MagicMock()), patch.object(
            main, "get_embedding", return_value=None
        ):
            result = await main.embed(FakeUploadFile())
        self.assertFalse(result.success)
        self.assertIsNone(result.embedding)

    async def test_embed_success(self):
        expected = unit_vector()
        with patch.object(main, "load_image", return_value=MagicMock()), patch.object(
            main, "get_embedding", return_value=expected
        ):
            result = await main.embed(FakeUploadFile())
        self.assertTrue(result.success)
        self.assertEqual(result.embedding, expected.tolist())

    async def test_verify_requires_both_models(self):
        for missing_model in ("mtcnn_model", "facenet_model"):
            with self.subTest(missing_model=missing_model):
                original = getattr(main, missing_model)
                setattr(main, missing_model, None)
                with self.assertRaises(HTTPException) as context:
                    await main.verify(
                        FakeUploadFile(), json.dumps(unit_vector().tolist())
                    )
                self.assertEqual(context.exception.status_code, 503)
                setattr(main, missing_model, original)

    async def test_verify_rejects_malformed_json(self):
        with self.assertRaises(HTTPException) as context:
            await main.verify(FakeUploadFile(), "[")
        self.assertEqual(context.exception.status_code, 400)

    async def test_verify_rejects_invalid_embedding(self):
        invalid = json.dumps([1.0, 2.0])
        with self.assertRaises(HTTPException) as context:
            await main.verify(FakeUploadFile(), invalid)
        self.assertEqual(context.exception.status_code, 422)

    async def test_verify_no_face(self):
        with patch.object(main, "load_image", return_value=MagicMock()), patch.object(
            main, "get_embedding", return_value=None
        ):
            result = await main.verify(FakeUploadFile(), json.dumps(unit_vector().tolist()))
        self.assertFalse(result.match)
        self.assertEqual(result.similarity, 0.0)

    async def test_verify_identical_embeddings_match(self):
        await self.assert_verify(unit_vector(0), expected_match=True, expected_similarity=1.0)

    async def test_verify_orthogonal_embeddings_do_not_match(self):
        await self.assert_verify(unit_vector(1), expected_match=False, expected_similarity=0.0)

    async def test_verify_threshold_boundary_matches(self):
        with patch.object(main, "load_image", return_value=MagicMock()), patch.object(
            main, "get_embedding", return_value=unit_vector()
        ), patch.object(
            main, "cosine_similarity", return_value=main.SIMILARITY_THRESHOLD
        ):
            result = await main.verify(
                FakeUploadFile(), json.dumps(unit_vector().tolist())
            )
        self.assertTrue(result.match)
        self.assertEqual(result.similarity, main.SIMILARITY_THRESHOLD)

    async def test_internal_exception_is_not_exposed(self):
        with patch.object(main, "load_image", side_effect=RuntimeError("secret detail")):
            with self.assertLogs(main.logger, level="ERROR") as logs:
                with self.assertRaises(HTTPException) as context:
                    await main.embed(FakeUploadFile())
        self.assertEqual(context.exception.status_code, 500)
        self.assertNotIn("secret detail", context.exception.detail)
        self.assertIn("secret detail", "\n".join(logs.output))

    async def assert_verify(self, candidate, expected_match, expected_similarity):
        with patch.object(main, "load_image", return_value=MagicMock()), patch.object(
            main, "get_embedding", return_value=candidate
        ):
            result = await main.verify(FakeUploadFile(), json.dumps(unit_vector(0).tolist()))
        self.assertEqual(result.match, expected_match)
        self.assertAlmostEqual(result.similarity, expected_similarity, places=4)


class LifespanTests(unittest.IsolatedAsyncioTestCase):
    async def test_lifespan_constructs_models_without_downloading_weights(self):
        fake_mtcnn = MagicMock()
        fake_facenet = MagicMock()
        fake_facenet.eval.return_value = fake_facenet
        fake_facenet.to.return_value = fake_facenet

        with patch.object(main, "FNMTCNN", return_value=fake_mtcnn) as mtcnn_ctor, patch.object(
            main, "InceptionResnetV1", return_value=fake_facenet
        ) as facenet_ctor, patch.object(main.torch.cuda, "is_available", return_value=False):
            async with main.lifespan(main.app):
                self.assertIs(main.mtcnn_model, fake_mtcnn)
                self.assertIs(main.facenet_model, fake_facenet)

        mtcnn_ctor.assert_called_once()
        facenet_ctor.assert_called_once_with(pretrained="vggface2")
        fake_facenet.eval.assert_called_once_with()
        self.assertIsNone(main.mtcnn_model)
        self.assertIsNone(main.facenet_model)


if __name__ == "__main__":
    unittest.main()
