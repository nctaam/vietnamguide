from __future__ import annotations

import hashlib
import subprocess
import sys
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
        subprocess.run([sys.executable, str(CONVERTER)], cwd=REPO_ROOT, check=True)
        first_hashes = self.generated_hashes()
        subprocess.run([sys.executable, str(CONVERTER)], cwd=REPO_ROOT, check=True)
        self.assertEqual(first_hashes, self.generated_hashes())

    def generated_hashes(self) -> dict[str, str]:
        return {
            path.name: hashlib.sha256(path.read_bytes()).hexdigest()
            for path in sorted(IMAGE_DIR.glob("home-hero*"))
            + sorted(IMAGE_DIR.glob("home-editorial*"))
        }


if __name__ == "__main__":
    unittest.main()
