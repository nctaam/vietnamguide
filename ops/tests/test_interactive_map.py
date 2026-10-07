# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Interactive Map Component.
Verifies shortcode registration, zero H2 tags, corridor data integrity,
canonical route linking, ARIA accessibility, and Anti-AI Slop compliance.
"""
import json
import os
import re
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


if __name__ == '__main__':
    unittest.main()
