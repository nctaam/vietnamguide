# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Interactive Map Component.
Verifies shortcode registration, zero H2 tags, corridor data integrity,
canonical route linking, ARIA accessibility, and Anti-AI Slop compliance.
"""
import json
import os
import re
import subprocess
import unittest

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
MAP_FILE = os.path.join(
    REPO_ROOT,
    'wordpress', 'wp-content', 'themes', 'vietnamguide-premium', 'inc',
    'guide-interactive-map.php'
)
REGISTRY_FILE = os.path.join(REPO_ROOT, 'ops', 'route_registry.json')

TIER1_CLICHES = [
    r'\bnestled\b',
    r'\bhidden gem[s]?\b',
    r'\brich tapestry\b',
    r'\bbreathtaking\b',
    r'\bpicturesque\b',
    r'\badventure awaits\b',
    r'\bmelting pot\b',
    r'\bdelve into\b',
    r'\bbucket-list\b',
    r'\bbustling metropolis\b',
    r'\bcaptivating blend\b',
    r'\bfeast for the senses\b',
    r'\bsymphony of flavors\b',
    r'\boasis of tranquility\b',
]


class TestInteractiveMap(unittest.TestCase):

    def setUp(self):
        self.assertTrue(os.path.isfile(MAP_FILE), f"Missing file: {MAP_FILE}")
        with open(MAP_FILE, 'r', encoding='utf-8') as f:
            self.content = f.read()

        self.assertTrue(os.path.isfile(REGISTRY_FILE), f"Missing registry: {REGISTRY_FILE}")
        with open(REGISTRY_FILE, 'r', encoding='utf-8') as f:
            self.registry = json.load(f)

    def test_shortcode_registration(self):
        """The component must register shortcode [vg_interactive_map]."""
        pattern = r"add_shortcode\(\s*['\"]vg_interactive_map['\"]\s*,\s*['\"]vg_interactive_map_shortcode['\"]\s*\)"
        self.assertRegex(self.content, pattern, "Must register vg_interactive_map shortcode")

    def test_zero_h2_in_component(self):
        """Interactive map must NEVER emit <h2> tags to avoid TOC pollution (Gate 5 rule)."""
        h2_matches = re.findall(r'<h2\b[^>]*>', self.content, re.IGNORECASE)
        self.assertEqual(len(h2_matches), 0, f"Found {len(h2_matches)} <h2> tags in guide-interactive-map.php")

    def test_all_six_corridors_defined(self):
        """All 6 strategic geographical corridors must be defined in the dataset."""
        expected_corridors = [
            'northern-highlands',
            'red-river-maritime',
            'central-heritage',
            'south-central-highlands',
            'southern-metropolis',
            'maritime-archipelagos',
        ]
        for corridor_id in expected_corridors:
            self.assertIn(f"'{corridor_id}'", self.content, f"Missing corridor: {corridor_id}")

    def test_corridor_routes_link_to_canonical_registry(self):
        """All primary route links from corridors must point to registered routes."""
        valid_paths = {entry['path'] for entry in self.registry.get('routes', [])}
        route_slugs = re.findall(r"'primary_route_slug'\s*=>\s*'([^']+)'", self.content)
        self.assertGreater(len(route_slugs), 0, "Must have corridor route slugs")
        for slug in route_slugs:
            normalized = slug.strip('/')
            self.assertIn(
                normalized,
                valid_paths,
                f"Corridor route slug '{slug}' not found in canonical route registry"
            )

    def test_factual_density_and_metrics_present(self):
        """Component must contain distance (km), transit hours, VND spend, and seasons."""
        self.assertIn('km', self.content)
        self.assertIn('VND', self.content)
        self.assertIn('USD', self.content)
        self.assertIn('hours', self.content)
        self.assertIn('optimal_dry_window', self.content)

    def test_anti_ai_slop_zero_cliches(self):
        """Component must strictly contain zero Tier 1 marketing clichés."""
        for cliche_pattern in TIER1_CLICHES:
            matches = re.findall(cliche_pattern, self.content, re.IGNORECASE)
            self.assertEqual(
                len(matches),
                0,
                f"Forbidden cliché '{cliche_pattern}' found in guide-interactive-map.php: {matches}"
            )

    def test_aria_accessibility_attributes(self):
        """Component must declare proper tablist, tab, tabpanel, and role attributes."""
        self.assertIn('role="tablist"', self.content)
        self.assertIn('role="tab"', self.content)
        self.assertIn('role="tabpanel"', self.content)
        self.assertIn('aria-selected=', self.content)
        self.assertIn('aria-controls=', self.content)

    def test_svg_coordinate_bounding_boxes(self):
        """All waypoint pins must have SVG coordinates within viewBox bounds [0, 320] x [0, 540]."""
        self.assertIn('viewBox="0 0 320 540"', self.content, "SVG viewBox must be defined as 0 0 320 540")
        coord_matches = re.findall(
            r"'coordinates'\s*=>\s*\[\s*'x'\s*=>\s*(\d+)\s*,\s*'y'\s*=>\s*(\d+)\s*\]",
            self.content
        )
        self.assertEqual(len(coord_matches), 6, "Must define coordinates for all 6 corridors")
        for x_str, y_str in coord_matches:
            x, y = int(x_str), int(y_str)
            self.assertGreaterEqual(x, 0, f"X coordinate {x} out of bounds (< 0)")
            self.assertLessEqual(x, 320, f"X coordinate {x} out of bounds (> 320)")
            self.assertGreaterEqual(y, 0, f"Y coordinate {y} out of bounds (< 0)")
            self.assertLessEqual(y, 540, f"Y coordinate {y} out of bounds (> 540)")

    def test_php_syntax_verification(self):
        """guide-interactive-map.php must pass php -l linting without syntax errors."""
        res = subprocess.run(['php', '-l', MAP_FILE], capture_output=True, text=True)
        self.assertEqual(
            res.returncode,
            0,
            f"PHP syntax error in {MAP_FILE}:\n{res.stdout}\n{res.stderr}"
        )

    def test_regional_transit_corridor_data_structure_completeness(self):
        """All 6 corridors must have complete required telemetry and non-empty values."""
        required_keys = [
            'id', 'name', 'vietnamese', 'focal_nodes', 'distance_from_hanoi',
            'transit_summary', 'transit_hours', 'typical_daily_spend', 'spend_usd',
            'optimal_dry_window', 'microclimate_verdict', 'field_caution',
            'primary_route_slug', 'primary_route_label', 'coordinates'
        ]
        corridors = [
            'northern-highlands',
            'red-river-maritime',
            'central-heritage',
            'south-central-highlands',
            'southern-metropolis',
            'maritime-archipelagos',
        ]
        for corridor in corridors:
            corridor_block = re.search(rf"'{corridor}'\s*=>\s*\[(.*?)^\s*\],", self.content, re.DOTALL | re.MULTILINE)
            self.assertIsNotNone(corridor_block, f"Corridor block '{corridor}' not found")
            block_text = corridor_block.group(1)
            for key in required_keys:
                self.assertIn(f"'{key}'", block_text, f"Corridor '{corridor}' missing key '{key}'")

    def test_svg_vector_markup_elements(self):
        """Map canvas must render S-curve vector path, waypoint pins, and pulse indicators."""
        self.assertIn('class="vg-map-svg"', self.content)
        self.assertIn('class="vg-map-spine"', self.content)
        self.assertIn('vg-map-pin', self.content)
        self.assertIn('vg-map-pin-pulse', self.content)
        self.assertIn('data-corridor-pin=', self.content)

    def test_enriched_field_telemetry_gps_and_transit_corridors(self):
        """Enriched corridors must feature verified GPS coordinates for mountain passes and island harbors."""
        self.assertIn('GPS 23.2389, 105.4183', self.content, "Must include Ma Pi Leng Pass GPS telemetry")
        self.assertIn('Mã Pí Lèng Pass', self.content)
        self.assertIn('Măng Đen', self.content)
        self.assertIn('Kon Tum', self.content)
        self.assertIn('GPS 14.6041, 108.2882', self.content, "Must include Mang Den plateau GPS telemetry")
        self.assertIn('Phú Quý', self.content)
        self.assertIn('Phan Thiết harbor', self.content)
        self.assertIn('GPS 10.9234, 108.1062', self.content, "Must include Phan Thiet harbor GPS telemetry")


if __name__ == '__main__':
    unittest.main()
