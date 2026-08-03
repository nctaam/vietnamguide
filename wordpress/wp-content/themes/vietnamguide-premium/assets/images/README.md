# Homepage editorial image provenance

These are Stitch-provided assets, not image generation.

- Stitch project: `Vietnam Travel Experience Portal` (`10120543989568032144`)
- Desktop screen: `85c390be32164a6e809f8affd0d442f7`
- Retrieved: `2026-07-27`
- Source extraction: unique URLs were extracted from the Stitch desktop HTML with `https://lh3\.googleusercontent\.com/aida-public/[A-Za-z0-9_-]+`; `=s0` was appended to retrieve the original asset.

## Sources and outputs

### Ha Long Bay hero

- Subject: Misty limestone karsts in Ha Long Bay at sunrise.
- Stitch URL (unique index 0): `https://lh3.googleusercontent.com/aida-public/AB6AXuCFsC5oDVqAkxKdFK5K-sTl-sZXnAxGN69qx5PhmnExKrxw3iKNDDPPhvA9x6SIkhzTbTe69NTaFUV7WikRJN-Rs5O7fRJyCJFQ-Jn2HsQQi7LzHoP3NMM8atcZ_hPTG1fLqpNUMFn5pAuItMwR65_SPpVdSPgzS0ZAHyi5_qDid2zbAzS_gty10yyeXNhTc-2DjIf5LO_YQh8ksQgCymHs73FqcUhqKkaTKNG2x4BNdHUBiryEaXokHurEF6in9pgZrGOEF_35p-qS`
- Source: `source-stitch-ha-long-bay.jpg` (JPEG, 1376 x 768).
- Outputs: `home-hero.jpg`, `home-hero.webp`, `home-hero-960.jpg`, `home-hero-960.webp`, `home-hero-640.jpg`, and `home-hero-640.webp`.

### Hoi An editorial image

- Subject: Lanterns reflected on the river in Hoi An at night.
- Stitch URL (unique index 2): `https://lh3.googleusercontent.com/aida-public/AB6AXuAc-ldpzyDi7cf9FVWKGHaJm0EaTUHEyI02mHcUSLPFowfBU-YJHpZdFqyoiEMBhjmIwGBP1sEIcizBrjtytytXVpeQLqs_mdOW8mw5VH4BX0XxK2mS1xeIgDMgJu0KwYYOQHWIAPzhD3c1EUnMD5xyOnFdUhPPbbKxgf3HlS-UQAIkdmUDEC7H23VIToNehKfUC99K7sEb6VdsCfCDT0k3zPK1hQaerS1aLQlffESWzdaWPGTnWabVWdQpWP2naPliHGalUCw9EzTI`
- Source: `source-stitch-hoi-an-lanterns.jpg` (JPEG, 1408 x 768).
- Outputs: `home-editorial.jpg`, `home-editorial.webp`, `home-editorial-720.jpg`, and `home-editorial-720.webp`. No 1200px variant is produced by the approved image set.

All derived files are built deterministically with Pillow by running `python ops/build-homepage-images.py`. The converter preserves aspect ratio, uses RGB output, and does not upscale source assets.

No embedded text, logo, or third-party trademark.
