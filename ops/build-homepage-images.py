from __future__ import annotations

import argparse
import re
from pathlib import Path

from PIL import Image, ImageOps


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

IMAGE_SETS = (
    ("source-stitch-ha-long-bay.jpg", "home-hero", (960, 640)),
    ("source-stitch-hoi-an-lanterns.jpg", "home-editorial", (720,)),
)
MANAGED_VARIANT = re.compile(r"^home-(?:hero|editorial)-\d+\.(?:jpg|webp)$")


def save_formats(image: Image.Image, output_dir: Path, output_stem: str) -> set[str]:
    output_dir.mkdir(parents=True, exist_ok=True)
    output_names = {f"{output_stem}.jpg", f"{output_stem}.webp"}
    image.save(
        output_dir / f"{output_stem}.jpg",
        format="JPEG",
        quality=84,
        optimize=True,
        progressive=True,
        subsampling="4:2:0",
    )
    image.save(
        output_dir / f"{output_stem}.webp",
        format="WEBP",
        quality=82,
        method=6,
    )
    return output_names


def build_image_set(
    image_dir: Path, output_dir: Path, source_name: str, output_stem: str, widths: tuple[int, ...]
) -> set[str]:
    with Image.open(image_dir / source_name) as source:
        base = ImageOps.exif_transpose(source).convert("RGB")

    output_names = save_formats(base, output_dir, output_stem)

    for width in widths:
        if width > base.width:
            continue
        height = round(base.height * width / base.width)
        variant = base.resize((width, height), Image.Resampling.LANCZOS)
        output_names.update(save_formats(variant, output_dir, f"{output_stem}-{width}"))

    return output_names


def remove_obsolete_variants(output_dir: Path, expected_names: set[str]) -> None:
    if not output_dir.is_dir():
        return
    for path in output_dir.iterdir():
        if path.is_file() and MANAGED_VARIANT.fullmatch(path.name) and path.name not in expected_names:
            path.unlink()


def build_all(image_dir: Path, output_dir: Path) -> None:
    expected_names: set[str] = set()
    for image_set in IMAGE_SETS:
        expected_names.update(build_image_set(image_dir, output_dir, *image_set))
    remove_obsolete_variants(output_dir, expected_names)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Build deterministic homepage image derivatives.")
    parser.add_argument("--image-dir", type=Path, default=IMAGE_DIR)
    parser.add_argument("--output-dir", type=Path, default=IMAGE_DIR)
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    build_all(args.image_dir, args.output_dir)


if __name__ == "__main__":
    main()
