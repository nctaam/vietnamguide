# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Interactive Travel Toolkit Components.
Verifies zero H2 heading pollution (TOC safety), unique DOM IDs, shortcode registrations,
and target slug coverage across all 4 interactive components.
"""
import os
import re
import subprocess
import unittest

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
THEME_INC = os.path.join(REPO_ROOT, 'wordpress', 'wp-content', 'themes', 'vietnamguide-premium', 'inc')

FILES = {
    'visa_checker': os.path.join(THEME_INC, 'guide-visa-checker.php'),
    'season_matrix': os.path.join(THEME_INC, 'guide-season-matrix.php'),
    'cost_calculator': os.path.join(THEME_INC, 'guide-cost-calculator.php'),
    'itinerary_finder': os.path.join(THEME_INC, 'guide-itinerary-finder.php'),
}


class TestInteractiveShortcodes(unittest.TestCase):

    def setUp(self):
        self.contents = {}
        for key, path in FILES.items():
            self.assertTrue(os.path.isfile(path), f"Missing file: {path}")
            with open(path, 'r', encoding='utf-8') as f:
                self.contents[key] = f.read()

    def test_shortcodes_registered(self):
        """All 4 components must register their corresponding shortcode."""
        expected_shortcodes = {
            'visa_checker': 'vg_visa_checker',
            'season_matrix': 'vg_season_matrix',
            'cost_calculator': 'vg_cost_calculator',
            'itinerary_finder': 'vg_itinerary_finder',
        }
        for key, sc in expected_shortcodes.items():
            pattern = rf"add_shortcode\(\s*['\"]{sc}['\"]\s*,"
            self.assertRegex(
                self.contents[key],
                pattern,
                f"File for {key} must register shortcode [{sc}]"
            )

    def test_zero_h2_in_components(self):
        """Interactive components must NEVER emit <h2> tags to avoid TOC pollution."""
        for key, content in self.contents.items():
            h2_matches = re.findall(r'<h2\b[^>]*>', content, re.IGNORECASE)
            self.assertEqual(
                len(h2_matches),
                0,
                f"{key} contains {len(h2_matches)} <h2> tag(s): {h2_matches}. Must use <div role='heading' aria-level='2'> instead."
            )

    def test_zero_duplicate_ids_in_each_component(self):
        """Each component must have unique element IDs internally."""
        for key, content in self.contents.items():
            # Find all id="..." in HTML markup (ignore PHP code comments)
            ids = re.findall(r'\bid=["\']([a-zA-Z0-9_\-]+)["\']', content)
            seen = set()
            duplicates = []
            for el_id in ids:
                if el_id in seen:
                    duplicates.append(el_id)
                seen.add(el_id)
            self.assertEqual(
                len(duplicates),
                0,
                f"{key} has duplicate DOM IDs: {duplicates}"
            )

    def test_target_slugs_in_auto_injection(self):
        """Auto-injection functions must cover strategic high-intent routes."""
        # Visa checker targets
        self.assertIn('vietnam-airport-arrival-checklist', self.contents['visa_checker'])
        self.assertIn('vietnam-first-trip-planning-checklist', self.contents['visa_checker'])
        self.assertIn('vietnam-evisa', self.contents['visa_checker'])

        # Season matrix targets
        self.assertIn('best-time-to-visit-vietnam', self.contents['season_matrix'])
        self.assertIn('best-time-for-northern-vietnam', self.contents['season_matrix'])
        self.assertIn('vietnam-rainy-season-flexible-route', self.contents['season_matrix'])

        # Cost calculator targets
        self.assertIn('vietnam-travel-cost', self.contents['cost_calculator'])
        self.assertIn('where-to-stay-in-vietnam-base-decisions', self.contents['cost_calculator'])

        # Itinerary finder targets
        self.assertIn('itineraries', self.contents['itinerary_finder'])
        self.assertIn('best-vietnam-routes-first-time-visitors', self.contents['itinerary_finder'])

    def test_php_syntax_linter(self):
        """All modified PHP files must pass php -l syntax check."""
        for key, path in FILES.items():
            res = subprocess.run(['php', '-l', path], capture_output=True, text=True)
            self.assertEqual(
                res.returncode,
                0,
                f"PHP syntax error in {path}:\n{res.stdout}\n{res.stderr}"
            )


if __name__ == '__main__':
    unittest.main()
