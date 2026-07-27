from __future__ import annotations

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


def save_formats(image: Image.Image, output_stem: str) -> None:
    image.save(
        IMAGE_DIR / f"{output_stem}.jpg",
        format="JPEG",
        quality=84,
        optimize=True,
        progressive=True,
        subsampling="4:2:0",
    )
    image.save(
        IMAGE_DIR / f"{output_stem}.webp",
        format="WEBP",
        quality=82,
        method=6,
    )


def build_image_set(source_name: str, output_stem: str, widths: tuple[int, ...]) -> None:
    with Image.open(IMAGE_DIR / source_name) as source:
        base = ImageOps.exif_transpose(source).convert("RGB")

    save_formats(base, output_stem)

    for width in widths:
        if width > base.width:
            continue
        height = round(base.height * width / base.width)
        variant = base.resize((width, height), Image.Resampling.LANCZOS)
        save_formats(variant, f"{output_stem}-{width}")


def main() -> None:
    for image_set in IMAGE_SETS:
        build_image_set(*image_set)


if __name__ == "__main__":
    main()
