# -*- coding: utf-8 -*-
"""
Generate VietnamGuide PWA and Site Icons:
- vg-icon.svg (Vector scalable)
- vg-icon-192.png (PWA standard)
- vg-icon-512.png (PWA splash / high-res)
"""
from pathlib import Path
from PIL import Image, ImageDraw

THEME_DIR = Path(__file__).resolve().parents[1] / "wordpress" / "wp-content" / "themes" / "vietnamguide-premium"
IMG_DIR = THEME_DIR / "assets" / "images"
IMG_DIR.mkdir(parents=True, exist_ok=True)

# 1. Create vg-icon.svg
svg_content = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">
  <defs>
    <linearGradient id="vgBg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0e6f5c"/>
      <stop offset="100%" stop-color="#06382e"/>
    </linearGradient>
    <linearGradient id="vgGold" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f3d484"/>
      <stop offset="50%" stop-color="#c5a059"/>
      <stop offset="100%" stop-color="#9d782f"/>
    </linearGradient>
    <filter id="vgShadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#000000" flood-opacity="0.4"/>
    </filter>
  </defs>

  <!-- Background Squircle -->
  <rect width="512" height="512" rx="108" fill="url(#vgBg)"/>
  <rect x="8" y="8" width="496" height="496" rx="100" fill="none" stroke="#c5a059" stroke-opacity="0.25" stroke-width="4"/>

  <!-- Golden Compass Star & Conical Hat Icon -->
  <g filter="url(#vgShadow)" transform="translate(256, 256)">
    <!-- Conical Hat (Non La) Contour -->
    <path d="M 0,-140 L 140,80 Q 0,115 -140,80 Z" fill="url(#vgGold)" stroke="#f3d484" stroke-width="3"/>
    <!-- Hat Rib Curves -->
    <path d="M -90,40 Q 0,65 90,40" fill="none" stroke="#06382e" stroke-width="3" stroke-opacity="0.5"/>
    <path d="M -50,-10 Q 0,10 50,-10" fill="none" stroke="#06382e" stroke-width="2.5" stroke-opacity="0.5"/>

    <!-- Compass North Star Indicator -->
    <path d="M 0,-165 L 14,-135 L 44,-135 L 20,-115 L 28,-85 L 0,-102 L -28,-85 L -20,-115 L -44,-135 L -14,-135 Z" fill="#fdf6e2" stroke="#c5a059" stroke-width="2"/>

    <!-- River / Sea Wave Base -->
    <path d="M -160,115 Q -80,95 0,115 Q 80,135 160,115" fill="none" stroke="#f3d484" stroke-width="6" stroke-linecap="round" stroke-opacity="0.85"/>
    <path d="M -120,135 Q -60,120 0,135 Q 60,150 120,135" fill="none" stroke="#c5a059" stroke-width="4" stroke-linecap="round" stroke-opacity="0.6"/>
  </g>
</svg>
"""

svg_path = IMG_DIR / "vg-icon.svg"
svg_path.write_text(svg_content.strip(), encoding="utf-8")
print(f"Written: {svg_path}")

# 2. Render high-res PNG icons using Pillow
def render_icon_png(size: int, output_path: Path):
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    scale = size / 512.0

    # Draw rounded background
    rx = int(108 * scale)
    draw.rounded_rectangle([0, 0, size - 1, size - 1], radius=rx, fill=(14, 111, 92, 255))
    # Border
    border_inset = int(8 * scale)
    border_rx = int(100 * scale)
    draw.rounded_rectangle(
        [border_inset, border_inset, size - 1 - border_inset, size - 1 - border_inset],
        radius=border_rx,
        outline=(197, 160, 89, 70),
        width=max(1, int(4 * scale))
    )

    cx, cy = size // 2, size // 2
    # Non La triangle/polygon
    p1 = (cx, int(cy - 140 * scale))
    p2 = (int(cx + 140 * scale), int(cy + 80 * scale))
    p3 = (cx, int(cy + 105 * scale))
    p4 = (int(cx - 140 * scale), int(cy + 80 * scale))
    draw.polygon([p1, p2, p3, p4], fill=(197, 160, 89, 255), outline=(243, 212, 132, 255))

    # Star at top
    star_r1 = int(28 * scale)
    star_r2 = int(12 * scale)
    star_cy = int(cy - 125 * scale)
    import math
    star_pts = []
    for i in range(10):
        r = star_r1 if i % 2 == 0 else star_r2
        angle = i * math.pi / 5 - math.pi / 2
        star_pts.append((cx + int(r * math.cos(angle)), star_cy + int(r * math.sin(angle))))
    draw.polygon(star_pts, fill=(253, 246, 226, 255), outline=(197, 160, 89, 255))

    # Waves below
    wave_y1 = int(cy + 115 * scale)
    wave_y2 = int(cy + 135 * scale)
    draw.line([(int(cx - 150 * scale), wave_y1), (int(cx + 150 * scale), wave_y1)], fill=(243, 212, 132, 220), width=max(2, int(6 * scale)))
    draw.line([(int(cx - 110 * scale), wave_y2), (int(cx + 110 * scale), wave_y2)], fill=(197, 160, 89, 180), width=max(1, int(4 * scale)))

    img.save(output_path, format="PNG", optimize=True)
    print(f"Rendered PNG: {output_path} ({size}x{size})")

render_icon_png(192, IMG_DIR / "vg-icon-192.png")
render_icon_png(512, IMG_DIR / "vg-icon-512.png")
print("All icons generated successfully.")
