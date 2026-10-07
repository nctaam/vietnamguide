# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Visual Dispatch & Photo Storytelling.
Verifies shortcode registration, zero H2 tags (Gate 5 TOC protection),
EXIF factual data fields, modal lightbox accessibility, and Anti-AI Slop compliance.
"""

import os
import re
import unittest

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
THEME_INC = os.path.join(REPO_ROOT, 'wordpress', 'wp-content', 'themes', 'vietnamguide-premium', 'inc')
PHOTO_DISPATCH_FILE = os.path.join(THEME_INC, 'guide-photo-dispatch.php')


class TestPhotoDispatchComponent(unittest.TestCase):

    def setUp(self):
        self.assertTrue(os.path.isfile(PHOTO_DISPATCH_FILE), f"Missing file: {PHOTO_DISPATCH_FILE}")
        with open(PHOTO_DISPATCH_FILE, 'r', encoding='utf-8') as f:
            self.content = f.read()

    def test_shortcode_registration(self):
        """Shortcode [vg_photo_dispatch] must be registered."""
        self.assertRegex(
            self.content,
            r"add_shortcode\(\s*['\"]vg_photo_dispatch['\"]\s*,\s*['\"]vg_render_photo_dispatch['\"]\s*\)",
            "Shortcode vg_photo_dispatch must be registered with vg_render_photo_dispatch"
        )

    def test_zero_h2_toc_protection(self):
        """Visual dispatch must NEVER emit <h2> tags to avoid TOC pollution (Gate 5 rule)."""
        h2_matches = re.findall(r'<h2\b[^>]*>', self.content, re.IGNORECASE)
        self.assertEqual(
            len(h2_matches),
            0,
            f"Gate 5 Violation: Found {len(h2_matches)} <h2> tags in guide-photo-dispatch.php: {h2_matches}"
        )

    def test_dispatches_data_structure(self):
        """Visual dispatches data array must have required editorial and EXIF fields."""
        required_fields = [
            'id', 'title', 'location', 'province', 'coordinates', 'lead',
            'base_image_slug', 'image_fallback', 'alt_text', 'exif',
            'field_notes', 'photographer', 'license', 'related_route'
        ]
        for field in required_fields:
            self.assertIn(f"'{field}'", self.content, f"Field '{field}' missing from dispatch configuration")

    def test_exif_telemetry_density(self):
        """EXIF pills must include essential optical parameters."""
        exif_keys = ['focal_length', 'aperture', 'shutter', 'iso', 'elevation', 'time_of_day']
        for key in exif_keys:
            self.assertIn(f"'{key}'", self.content, f"EXIF telemetry key '{key}' missing")

    def test_lightbox_accessibility_contracts(self):
        """Lightbox modal must have dialog role, aria-modal, and escape key listener."""
        self.assertIn('role="dialog"', self.content, "Modal must declare role='dialog'")
        self.assertIn('aria-modal="true"', self.content, "Modal must declare aria-modal='true'")
        self.assertIn("key === 'Escape'", self.content, "Modal must close on Escape key")
        self.assertIn("document.body.style.overflow = 'hidden'", self.content, "Modal must trap scroll when active")

    def test_zero_tier1_cliches(self):
        """Visual dispatch copy must be free of generic AI-slop clichés."""
        cliches = [
            r"\bnestled\b",
            r"\bhidden gem\b",
            r"\brich tapestry\b",
            r"\bbreathtaking\b",
            r"\bpicturesque\b",
            r"\bjourney through time\b",
            r"\badventure awaits\b",
            r"\bmelting pot\b"
        ]
        for pattern in cliches:
            match = re.search(pattern, self.content, re.IGNORECASE)
            self.assertIsNone(
                match,
                f"Anti-AI Slop Violation: Found cliché '{pattern}' in guide-photo-dispatch.php"
            )


if __name__ == '__main__':
    unittest.main()
