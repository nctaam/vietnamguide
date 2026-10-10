# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Visual Dispatch & Photo Storytelling.
Verifies shortcode registration, zero H2 tags (Gate 5 TOC protection),
EXIF factual data fields, modal lightbox accessibility, and Anti-AI Slop compliance.
"""

import os
import re
import subprocess
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

    def test_php_syntax_verification(self):
        """guide-photo-dispatch.php must pass php -l syntax check."""
        res = subprocess.run(['php', '-l', PHOTO_DISPATCH_FILE], capture_output=True, text=True)
        self.assertEqual(
            res.returncode,
            0,
            f"PHP syntax error in {PHOTO_DISPATCH_FILE}:\n{res.stdout}\n{res.stderr}"
        )

    def test_layout_modifier_classes(self):
        """Layout modifier classes ('standard', 'fullwidth', 'compact') must be correctly emitted without PHP notices."""
        self.assertIn("vg-photo-dispatch--<?php echo esc_attr($atts['layout']); ?>", self.content)
        for layout in ['standard', 'fullwidth', 'compact']:
            self.assertIn(f"'{layout}'", self.content)

        php_code = f"""
        define('ABSPATH', 1);
        function add_shortcode($tag, $callback) {{}}
        function add_action($tag, $callback, $priority = 10) {{}}
        function sanitize_key($k) {{ return preg_replace('/[^a-z0-9_-]/', '', strtolower($k)); }}
        function shortcode_atts($pairs, $atts, $shortcode = '') {{
            $out = [];
            foreach ($pairs as $k => $def) {{
                $out[$k] = array_key_exists($k, (array)$atts) ? $atts[$k] : $def;
            }}
            return $out;
        }}
        function get_template_directory_uri() {{ return 'https://vietnamguide.net/wp-content/themes/vietnamguide-premium'; }}
        function esc_url($u) {{ return htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); }}
        function esc_attr($a) {{ return htmlspecialchars($a, ENT_QUOTES, 'UTF-8'); }}
        function esc_html($h) {{ return htmlspecialchars($h, ENT_QUOTES, 'UTF-8'); }}
        function home_url($p = '') {{ return 'https://vietnamguide.net' . $p; }}
        require_once '{PHOTO_DISPATCH_FILE.replace(chr(92), "/")}';
        foreach (['standard', 'fullwidth', 'compact'] as $layout) {{
            $output = vg_render_photo_dispatch(['slug' => 'ha-long-dawn', 'layout' => $layout]);
            if (strpos($output, 'vg-photo-dispatch--' . $layout) === false) {{
                fwrite(STDERR, "Missing modifier class for layout: $layout\\n");
                exit(1);
            }}
        }}
        """
        res = subprocess.run(['php', '-r', php_code], capture_output=True, text=True)
        self.assertEqual(
            res.returncode,
            0,
            f"Execution error while verifying layout modifiers:\n{res.stdout}\n{res.stderr}"
        )

    def test_fallback_image_files_exist_on_disk(self):
        """Fallback image files referenced in dispatches (ha-long-bay-vietnam-hero.jpg, home-editorial.jpg) must exist on disk."""
        theme_root = os.path.dirname(THEME_INC)
        image_fallbacks = re.findall(r"'image_fallback'\s*=>\s*'([^']+)'", self.content)
        self.assertGreater(len(image_fallbacks), 0, "Dispatches must reference fallback images")
        for img_rel in image_fallbacks:
            img_path = os.path.join(theme_root, img_rel)
            self.assertTrue(
                os.path.isfile(img_path),
                f"Referenced fallback image not found on disk: {img_path}"
            )
        self.assertTrue(os.path.isfile(os.path.join(theme_root, 'assets', 'images', 'ha-long-bay-vietnam-hero.jpg')))
        self.assertTrue(os.path.isfile(os.path.join(theme_root, 'assets', 'images', 'home-editorial.jpg')))

    def test_optical_telemetry_and_coordinates_format(self):
        """Dispatches must declare valid geographic coordinates and optical telemetry."""
        coord_matches = re.findall(r"'coordinates'\s*=>\s*'((?:\\'|[^'])+)'", self.content)
        self.assertGreaterEqual(len(coord_matches), 2, "Must define coordinates for at least 2 dispatches")
        for coords in coord_matches:
            self.assertTrue('N' in coords and 'E' in coords, f"Coordinates must have North and East markers: {coords}")
            self.assertRegex(coords, r"\d+.*N\s+\d+.*E", f"Invalid coordinates format: {coords}")
        self.assertIn("'ha-long-dawn'", self.content)
        self.assertIn("'hoi-an-dusk'", self.content)
        self.assertIn("'sapa-terraces'", self.content)

    def test_dispatch_tab_switcher_markup_and_accessibility(self):
        """Dispatches suite must emit role='tablist', role='tab', and role='tabpanel' with data attributes."""
        self.assertIn('class="vg-dispatch-pill-selector"', self.content)
        self.assertIn('role="tablist"', self.content)
        self.assertIn('role="tab"', self.content)
        self.assertIn('role="tabpanel"', self.content)
        self.assertIn('data-dispatch-target=', self.content)
        self.assertIn('data-dispatch-panel=', self.content)
        self.assertIn('data-vg-photo-dispatch-suite', self.content)


if __name__ == '__main__':
    unittest.main()
