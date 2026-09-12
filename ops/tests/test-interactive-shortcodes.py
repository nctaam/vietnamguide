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
    'airport_navigator': os.path.join(THEME_INC, 'guide-airport-navigator.php'),
}
SEO_FILE = os.path.join(THEME_INC, 'guide-seo.php')


class TestInteractiveShortcodes(unittest.TestCase):

    def setUp(self):
        self.contents = {}
        for key, path in FILES.items():
            self.assertTrue(os.path.isfile(path), f"Missing file: {path}")
            with open(path, 'r', encoding='utf-8') as f:
                self.contents[key] = f.read()

    def test_shortcodes_registered(self):
        """All 5 components must register their corresponding shortcode."""
        expected_shortcodes = {
            'visa_checker': 'vg_visa_checker',
            'season_matrix': 'vg_season_matrix',
            'cost_calculator': 'vg_cost_calculator',
            'itinerary_finder': 'vg_itinerary_finder',
            'airport_navigator': 'vg_airport_navigator',
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

        # Airport navigator targets
        self.assertIn('vietnam-airport-arrival-checklist', self.contents['airport_navigator'])
        self.assertIn('transport-within-vietnam', self.contents['airport_navigator'])
        self.assertIn('safety-and-scams-in-vietnam', self.contents['airport_navigator'])
        self.assertIn('vietnam-first-trip-planning-checklist', self.contents['airport_navigator'])

    def test_php_syntax_linter(self):
        """All modified PHP files must pass php -l syntax check."""
        for key, path in FILES.items():
            res = subprocess.run(['php', '-l', path], capture_output=True, text=True)
            self.assertEqual(
                res.returncode,
                0,
                f"PHP syntax error in {path}:\n{res.stdout}\n{res.stderr}"
            )


    def test_aria_live_regions(self):
        """All 5 interactive components must contain aria-live='polite' for WCAG 2.2 AA dynamic announcements."""
        for key, content in self.contents.items():
            self.assertIn(
                'aria-live="polite"',
                content,
                f"Component {key} must have at least one element with aria-live='polite' for screen reader accessibility."
            )

    def test_session_storage_continuity(self):
        """Interactive components must utilize sessionStorage for seamless user state continuity."""
        session_keys = ['cost_calculator', 'airport_navigator', 'itinerary_finder', 'visa_checker', 'season_matrix']
        for key in session_keys:
            self.assertIn(
                'sessionStorage',
                self.contents[key],
                f"Component {key} must use sessionStorage for cross-tool state persistence."
            )

    def test_cross_tool_synergy_links(self):
        """Components must feature cross-tool synergy links connecting the travel planning workflow."""
        # Airport navigator links to visa and cost calculator
        self.assertIn('/plan/vietnam-evisa/', self.contents['airport_navigator'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['airport_navigator'])

        # Cost calculator links to visa and itineraries
        self.assertIn('/plan/vietnam-evisa/', self.contents['cost_calculator'])
        self.assertIn('/itineraries/', self.contents['cost_calculator'])

        # Itinerary finder links to climate/seasonality and costs
        self.assertIn('/plan/best-time-to-visit-vietnam/', self.contents['itinerary_finder'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['itinerary_finder'])

        # Visa checker links to itineraries, costs, and weather
        self.assertIn('/itineraries/', self.contents['visa_checker'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['visa_checker'])

        # Season matrix links to itinerary finder
        self.assertIn('/itineraries/', self.contents['season_matrix'])

    def test_url_query_sync_presence(self):
        """All 5 interactive components must support URL query/hash parameter state synchronization."""
        for key, content in self.contents.items():
            self.assertTrue(
                ('URLSearchParams' in content or 'window.location.search' in content or 'window.location.hash' in content) and 'history.replaceState' in content,
                f"Component {key} must support bidirectional URL parameter state sync via history.replaceState."
            )

    def test_storage_fallback_resilience(self):
        """Interactive components must feature resilient storage with fallback handling."""
        for key, content in self.contents.items():
            self.assertTrue(
                'localStorage' in content and 'try' in content,
                f"Component {key} must feature exception-safe storage access with localStorage fallback."
            )

    def test_noscript_fallback_presence(self):
        """All 5 interactive components must contain a semantic <noscript> fallback block."""
        for key, content in self.contents.items():
            self.assertIn(
                '<noscript>',
                content,
                f"Component {key} must contain a <noscript> block for zero-JS resilience."
            )

    def test_input_bounds_clamping_and_whitelisting(self):
        """Cost calculator and interactive widgets must clamp numerical inputs with Math.min/Math.max."""
        self.assertIn(
            'Math.max',
            self.contents['cost_calculator'],
            "Cost calculator must clamp duration and passenger inputs."
        )
        self.assertIn(
            'Math.min',
            self.contents['cost_calculator'],
            "Cost calculator must clamp duration and passenger inputs."
        )

    def test_schema_geocoordinates_coverage(self):
        """Destination schema must feature GeoCoordinates (latitude, longitude) for rich snippets."""
        self.assertTrue(os.path.isfile(SEO_FILE), f"Missing SEO file: {SEO_FILE}")
        with open(SEO_FILE, 'r', encoding='utf-8') as f:
            seo_content = f.read()
        self.assertIn('GeoCoordinates', seo_content, "Schema must generate GeoCoordinates type.")
        self.assertIn("'latitude'", seo_content, "Cluster registry must specify latitude.")
        self.assertIn("'longitude'", seo_content, "Cluster registry must specify longitude.")


if __name__ == '__main__':
    unittest.main()

