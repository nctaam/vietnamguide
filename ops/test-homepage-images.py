from __future__ import annotations

import hashlib
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from PIL import Image


REPO_ROOT = Path(__file__).resolve().parents[1]
IMAGE_DIR = (
    REPO_ROOT
    / "wordpress"
    / "wp-content"
    / "themes"
    / "vietnamguide-premium"
    / "assets"
    / "images"
)
CONVERTER = REPO_ROOT / "ops" / "build-homepage-images.py"


class HomepageImageTests(unittest.TestCase):
    def source_path(self, subject: str) -> Path:
        matches = list(IMAGE_DIR.glob(f"source-stitch-{subject}.*"))
        self.assertEqual(1, len(matches), f"Expected one source image for {subject}")
        return matches[0]

    def assert_real_extension(self, path: Path) -> None:
        suffixes = {"JPEG": {".jpg", ".jpeg"}, "PNG": {".png"}, "WEBP": {".webp"}}
        with Image.open(path) as image:
            self.assertIn(path.suffix.lower(), suffixes[image.format])

    def assert_image(self, path: Path, expected_format: str, expected_size: tuple[int, int]) -> None:
        self.assertTrue(path.is_file(), f"Missing image: {path.name}")
        self.assertGreater(path.stat().st_size, 0, f"Empty image: {path.name}")
        with Image.open(path) as image:
            self.assertEqual(expected_format, image.format)
            self.assertEqual("RGB", image.mode)
            self.assertEqual(expected_size, image.size)
            image.verify()

    def test_source_extensions_match_their_formats(self) -> None:
        self.assert_real_extension(self.source_path("ha-long-bay"))
        self.assert_real_extension(self.source_path("hoi-an-lanterns"))

    def test_generated_formats_and_responsive_dimensions(self) -> None:
        cases = (
            ("home-hero", "ha-long-bay", (960, 640)),
            ("home-editorial", "hoi-an-lanterns", (720,)),
        )

        for output_stem, subject, target_widths in cases:
            with Image.open(self.source_path(subject)) as source:
                source_size = source.size

            for extension, image_format in (("jpg", "JPEG"), ("webp", "WEBP")):
                self.assert_image(
                    IMAGE_DIR / f"{output_stem}.{extension}", image_format, source_size
                )

                for width in target_widths:
                    variant = IMAGE_DIR / f"{output_stem}-{width}.{extension}"
                    if width <= source_size[0]:
                        expected_height = round(source_size[1] * width / source_size[0])
                        self.assert_image(variant, image_format, (width, expected_height))
                    else:
                        self.assertFalse(variant.exists(), f"Upscaled variant exists: {variant.name}")

        self.assertFalse((IMAGE_DIR / "home-editorial-1200.jpg").exists())
        self.assertFalse((IMAGE_DIR / "home-editorial-1200.webp").exists())
        self.assertLessEqual((IMAGE_DIR / "home-hero.webp").stat().st_size, 700 * 1024)

    def test_converter_is_deterministic(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            converter, image_dir, output_dir = self.fixture_paths(Path(temp_dir))
            self.run_converter(converter, image_dir, output_dir)
            first_hashes = self.generated_hashes(output_dir)
            self.assertTrue(first_hashes, "Converter did not write isolated outputs")
            self.run_converter(converter, image_dir, output_dir)
            self.assertEqual(first_hashes, self.generated_hashes(output_dir))

    def test_converter_removes_stale_variants_after_source_shrinks(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            converter, image_dir, output_dir = self.fixture_paths(Path(temp_dir))
            self.run_converter(converter, image_dir, output_dir)
            self.assertTrue((output_dir / "home-hero-960.webp").is_file())
            unrelated = output_dir / "home-hero-poster.jpg"
            unrelated.write_bytes(b"unrelated asset")

            source_path = image_dir / "source-stitch-ha-long-bay.jpg"
            with Image.open(source_path) as source:
                shrunk = source.resize((800, round(source.height * 800 / source.width)))
            shrunk.save(source_path, format="JPEG")

            self.run_converter(converter, image_dir, output_dir)
            self.assertFalse((output_dir / "home-hero-960.jpg").exists())
            self.assertFalse((output_dir / "home-hero-960.webp").exists())
            self.assertTrue((output_dir / "home-hero-640.jpg").is_file())
            self.assertTrue((output_dir / "home-hero-640.webp").is_file())
            self.assertEqual(b"unrelated asset", unrelated.read_bytes())

    def test_isolated_converter_does_not_repair_corrupt_checkout_asset(self) -> None:
        with tempfile.TemporaryDirectory() as temp_dir:
            converter, image_dir, output_dir = self.fixture_paths(Path(temp_dir))
            corrupt_asset = image_dir / "home-hero.jpg"
            corrupt_asset.write_bytes(b"corrupt tracked payload")

            self.run_converter(converter, image_dir, output_dir)

            self.assertEqual(b"corrupt tracked payload", corrupt_asset.read_bytes())
            self.assertTrue((output_dir / "home-hero.jpg").is_file())

    def fixture_paths(self, temp_root: Path) -> tuple[Path, Path, Path]:
        fixture_root = temp_root / "fixture"
        converter = fixture_root / "ops" / CONVERTER.name
        image_dir = (
            fixture_root
            / "wordpress"
            / "wp-content"
            / "themes"
            / "vietnamguide-premium"
            / "assets"
            / "images"
        )
        output_dir = temp_root / "generated"
        converter.parent.mkdir(parents=True)
        image_dir.mkdir(parents=True)
        shutil.copy2(CONVERTER, converter)
        for source in IMAGE_DIR.glob("source-stitch-*"):
            shutil.copy2(source, image_dir / source.name)
        return converter, image_dir, output_dir

    def run_converter(self, converter: Path, image_dir: Path, output_dir: Path) -> None:
        subprocess.run(
            [
                sys.executable,
                str(converter),
                "--image-dir",
                str(image_dir),
                "--output-dir",
                str(output_dir),
            ],
            check=True,
        )

    def generated_hashes(self, image_dir: Path) -> dict[str, str]:
        return {
            path.name: hashlib.sha256(path.read_bytes()).hexdigest()
            for path in sorted(image_dir.glob("home-hero*"))
            + sorted(image_dir.glob("home-editorial*"))
        }


if __name__ == "__main__":
    unittest.main()
