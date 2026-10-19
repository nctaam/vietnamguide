# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Interactive Travel Toolkit Components.
Verifies zero H2 heading pollution (TOC safety), unique DOM IDs, shortcode registrations,
and target slug coverage across all 4 interactive components.
"""
import glob
import os
import re
import subprocess
import unittest

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
THEME_INC = os.path.join(REPO_ROOT, 'wordpress', 'wp-content', 'themes', 'vietnamguide-premium', 'inc')
THEME_DIR = os.path.join(REPO_ROOT, 'wordpress', 'wp-content', 'themes', 'vietnamguide-premium')
HTACCESS_FILE = os.path.join(REPO_ROOT, 'wordpress', '.htaccess')

FILES = {
    'visa_checker': os.path.join(THEME_INC, 'guide-visa-checker.php'),
    'season_matrix': os.path.join(THEME_INC, 'guide-season-matrix.php'),
    'cost_calculator': os.path.join(THEME_INC, 'guide-cost-calculator.php'),
    'itinerary_finder': os.path.join(THEME_INC, 'guide-itinerary-finder.php'),
    'airport_navigator': os.path.join(THEME_INC, 'guide-airport-navigator.php'),
    'packing_checklist': os.path.join(THEME_INC, 'guide-packing-checklist.php'),
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
        """All 6 components must register their corresponding shortcode."""
        expected_shortcodes = {
            'visa_checker': 'vg_visa_checker',
            'season_matrix': 'vg_season_matrix',
            'cost_calculator': 'vg_cost_calculator',
            'itinerary_finder': 'vg_itinerary_finder',
            'airport_navigator': 'vg_airport_navigator',
            'packing_checklist': 'vg_packing_checklist',
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

        # Packing checklist targets
        self.assertIn('vietnam-first-trip-planning-checklist', self.contents['packing_checklist'])
        self.assertIn('vietnam-airport-arrival-checklist', self.contents['packing_checklist'])

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
        """All interactive components must contain aria-live='polite' for WCAG 2.2 AA dynamic announcements."""
        for key, content in self.contents.items():
            self.assertIn(
                'aria-live="polite"',
                content,
                f"Component {key} must have at least one element with aria-live='polite' for screen reader accessibility."
            )

    def test_session_storage_continuity(self):
        """Interactive components must utilize sessionStorage for seamless user state continuity."""
        session_keys = ['cost_calculator', 'airport_navigator', 'itinerary_finder', 'visa_checker', 'season_matrix', 'packing_checklist']
        for key in session_keys:
            self.assertIn(
                'sessionStorage',
                self.contents[key],
                f"Component {key} must use sessionStorage for cross-tool state persistence."
            )

    def test_cross_tool_synergy_links(self):
        """Components must feature cross-tool synergy links connecting the travel planning workflow."""
        # Airport navigator links to visa, cost calculator, weather, itineraries, and packing checklist
        self.assertIn('/plan/vietnam-evisa/', self.contents['airport_navigator'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['airport_navigator'])
        self.assertIn('/plan/best-time-to-visit-vietnam/', self.contents['airport_navigator'])
        self.assertIn('/itineraries/', self.contents['airport_navigator'])
        self.assertIn('/plan/vietnam-first-trip-planning-checklist/', self.contents['airport_navigator'])

        # Cost calculator links to visa, itineraries, airport, weather, and packing checklist
        self.assertIn('/plan/vietnam-evisa/', self.contents['cost_calculator'])
        self.assertIn('/itineraries/', self.contents['cost_calculator'])
        self.assertIn('/plan/vietnam-airport-arrival-checklist/', self.contents['cost_calculator'])
        self.assertIn('/plan/best-time-to-visit-vietnam/', self.contents['cost_calculator'])
        self.assertIn('/plan/vietnam-first-trip-planning-checklist/', self.contents['cost_calculator'])

        # Itinerary finder links to climate/seasonality, costs, visa, airport, and packing checklist
        self.assertIn('/plan/best-time-to-visit-vietnam/', self.contents['itinerary_finder'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['itinerary_finder'])
        self.assertIn('/plan/vietnam-evisa/', self.contents['itinerary_finder'])
        self.assertIn('/plan/vietnam-airport-arrival-checklist/', self.contents['itinerary_finder'])
        self.assertIn('/plan/vietnam-first-trip-planning-checklist/', self.contents['itinerary_finder'])

        # Visa checker links to itineraries, airport, costs, weather, and packing checklist
        self.assertIn('/itineraries/', self.contents['visa_checker'])
        self.assertIn('/plan/vietnam-airport-arrival-checklist/', self.contents['visa_checker'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['visa_checker'])
        self.assertIn('/plan/best-time-to-visit-vietnam/', self.contents['visa_checker'])
        self.assertIn('/plan/vietnam-first-trip-planning-checklist/', self.contents['visa_checker'])

        # Season matrix links to visa, costs, airport, itineraries, and packing checklist
        self.assertIn('/plan/vietnam-evisa/', self.contents['season_matrix'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['season_matrix'])
        self.assertIn('/plan/vietnam-airport-arrival-checklist/', self.contents['season_matrix'])
        self.assertIn('/itineraries/', self.contents['season_matrix'])
        self.assertIn('/plan/vietnam-first-trip-planning-checklist/', self.contents['season_matrix'])

        # Packing checklist links to visa, costs, best time, airport, and itineraries
        self.assertIn('/plan/vietnam-evisa/', self.contents['packing_checklist'])
        self.assertIn('/costs/vietnam-travel-cost/', self.contents['packing_checklist'])
        self.assertIn('/plan/best-time-to-visit-vietnam/', self.contents['packing_checklist'])
        self.assertIn('/plan/vietnam-airport-arrival-checklist/', self.contents['packing_checklist'])
        self.assertIn('/itineraries/', self.contents['packing_checklist'])
        self.assertIn('/itineraries/10-days-in-vietnam/', self.contents['packing_checklist'])

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

    def test_wcag_aaa_aria_live_announcers(self):
        """All 5 interactive shortcodes must include an aria-live='polite' region for screen reader feedback."""
        for name, path in FILES.items():
            with open(path, 'r', encoding='utf-8') as f:
                code = f.read()
            self.assertIn('aria-live="polite"', code, f"{name} must provide an aria-live='polite' status container.")
            self.assertIn('role="status"', code, f"{name} must assign role='status' to live announcer.")

    def test_keyboard_accessibility_contracts(self):
        """Interactive chips and pills must support keyboard focus and Enter/Space event handlers."""
        for name, path in FILES.items():
            with open(path, 'r', encoding='utf-8') as f:
                code = f.read()
            self.assertTrue('keydown' in code or 'tabindex="0"' in code or '<button' in code,
                            f"{name} must support accessible keyboard interaction.")

    def test_schema_postal_address_and_map(self):
        """TouristDestination schema must feature PostalAddress (addressRegion, addressCountry) and hasMap."""
        with open(SEO_FILE, 'r', encoding='utf-8') as f:
            seo_content = f.read()
        self.assertIn('PostalAddress', seo_content, "Schema must generate PostalAddress type.")
        self.assertIn("'addressRegion'", seo_content, "Cluster registry must specify addressRegion.")
        self.assertIn("'hasMap'", seo_content, "Destination node must feature hasMap property.")

    def test_schema_wikidata_knowledge_graph_links(self):
        """TouristDestination schema must feature canonical Wikidata and Wikipedia sameAs links."""
        with open(SEO_FILE, 'r', encoding='utf-8') as f:
            seo_content = f.read()
        self.assertIn("'wikidata'", seo_content)
        self.assertIn("'wikipedia'", seo_content)
        self.assertIn('https://www.wikidata.org/wiki/Q1858', seo_content)  # Hanoi
        self.assertIn('https://www.wikidata.org/wiki/Q190128', seo_content)  # Ha Long
        self.assertIn('https://www.wikidata.org/wiki/Q36352', seo_content)  # Ninh Binh
        self.assertIn('https://www.wikidata.org/wiki/Q25282', seo_content)  # Da Nang
        self.assertIn('https://www.wikidata.org/wiki/Q1854', seo_content)  # HCMC
        self.assertIn('https://en.wikipedia.org/wiki/Hanoi', seo_content)
        self.assertIn('https://en.wikipedia.org/wiki/Vietnam', seo_content)

    def test_multi_currency_support(self):
        """Cost calculator must support 5 major currencies: USD, VND, EUR, GBP, AUD."""
        calc_content = self.contents['cost_calculator']
        currencies = ['USD', 'VND', 'EUR', 'GBP', 'AUD']
        for cur in currencies:
            self.assertIn(
                f'data-currency="{cur}"',
                calc_content,
                f"Cost calculator must contain a currency button for {cur}."
            )
            self.assertIn(
                f"'{cur}'",
                calc_content,
                f"Cost calculator JS must register currency {cur}."
            )
        self.assertIn('USD_TO_EUR', calc_content)
        self.assertIn('USD_TO_GBP', calc_content)
        self.assertIn('USD_TO_AUD', calc_content)
        self.assertIn('formatCurrency', calc_content)

    def test_viral_social_share_bars(self):
        """All 6 interactive toolkits must feature a viral share bar with WhatsApp, Telegram, and Copy Link."""
        for key, content in self.contents.items():
            self.assertIn(
                'vg-tool-share-bar',
                content,
                f"Toolkit {key} must contain a .vg-tool-share-bar container."
            )
            self.assertIn(
                'vg-btn-share--wa',
                content,
                f"Toolkit {key} must feature a 1-click WhatsApp share button."
            )
            self.assertIn(
                'vg-btn-share--tg',
                content,
                f"Toolkit {key} must feature a 1-click Telegram share button."
            )
            self.assertIn(
                'showToast',
                content,
                f"Toolkit {key} must implement interactive toast feedback on link copy."
            )

    def test_telemetry_vgtrack_integration(self):
        """All 6 interactive toolkits must dispatch telemetry events via window.vgTrack."""
        expected_events = {
            'cost_calculator': 'vg_calc_budget',
            'visa_checker': 'vg_visa_check',
            'season_matrix': 'vg_weather_select',
            'airport_navigator': 'vg_airport_view',
            'itinerary_finder': 'vg_itinerary_filter',
            'packing_checklist': 'vg_checklist_toggle',
        }
        for key, event_name in expected_events.items():
            self.assertIn(
                event_name,
                self.contents[key],
                f"Toolkit {key} must dispatch telemetry event '{event_name}'."
            )

    def test_rss_feed_page_inclusion(self):
        """guide-seo.php must filter main RSS query to include 'page' post type."""
        with open(SEO_FILE, 'r', encoding='utf-8') as f:
            seo_code = f.read()
        self.assertIn("['page', 'post']", seo_code, "Main RSS query must include pages and posts.")

    def test_mobile_qr_share_integration(self):
        """All 6 interactive toolkits must feature a QR to Phone share button and window.vgOpenQrModal."""
        for key, content in self.contents.items():
            self.assertIn(
                'vg-btn-share--qr',
                content,
                f"Toolkit {key} must feature a .vg-btn-share--qr button for mobile transition."
            )
            self.assertIn(
                'vgOpenQrModal',
                content,
                f"Toolkit {key} must wire click event to window.vgOpenQrModal."
            )

    def test_reading_experience_and_floating_dock(self):
        """Guide experience template and assets must implement reading progress and floating dock."""
        guide_part = os.path.join(THEME_DIR, 'template-parts', 'guide-page.php')
        css_file = os.path.join(THEME_DIR, 'assets', 'css', 'homepage.css')
        analytics_file = os.path.join(THEME_DIR, 'inc', 'guide-analytics.php')

        with open(guide_part, 'r', encoding='utf-8') as f:
            gp_code = f.read()
        self.assertIn('vg-reading-progress', gp_code, "guide-page.php must contain reading progress bar.")
        self.assertIn('vg-floating-dock', gp_code, "guide-page.php must contain floating tactical dock.")

        with open(css_file, 'r', encoding='utf-8') as f:
            css_code = f.read()
        self.assertIn('.vg-reading-progress', css_code, "CSS must style reading progress.")
        self.assertIn('.vg-floating-dock', css_code, "CSS must style floating dock.")
        self.assertIn('.vg-qr-modal', css_code, "CSS must style QR modal.")

        with open(analytics_file, 'r', encoding='utf-8') as f:
            analytics_code = f.read()
        self.assertIn('window.vgOpenQrModal', analytics_code, "guide-analytics.php must define window.vgOpenQrModal.")

    def test_security_htaccess_hardening(self):
        """wordpress/.htaccess must block scanner probes, xmlrpc, and brute-force wp-login."""
        htaccess_file = HTACCESS_FILE
        self.assertTrue(os.path.isfile(htaccess_file), f"Missing .htaccess at {htaccess_file}")
        with open(htaccess_file, 'r', encoding='utf-8') as f:
            ht_code = f.read()
        self.assertIn('.env', ht_code, ".htaccess must block .env scanner probes.")
        self.assertIn('xmlrpc', ht_code, ".htaccess must block xmlrpc.php.")
        self.assertIn('access=editorial', ht_code, ".htaccess must protect wp-login.php with editorial access key.")

    def test_search_engine_discovery_and_auto_ping(self):
        """guide-seo.php must implement Webmaster verification tags, endpoints, and non-blocking Auto-Ping engine."""
        seo_file = os.path.join(THEME_DIR, 'inc', 'guide-seo.php')
        self.assertTrue(os.path.isfile(seo_file), f"Missing guide-seo.php at {seo_file}")
        with open(seo_file, 'r', encoding='utf-8') as f:
            seo_code = f.read()

        # 1. Webmaster meta verification tags
        self.assertIn('google-site-verification', seo_code, "Must output google-site-verification meta tag.")
        self.assertIn('msvalidate.01', seo_code, "Must output msvalidate.01 meta tag for Bing Webmaster.")

        # 2. Webmaster verification virtual endpoints
        self.assertIn('vg_handle_webmaster_verification_endpoints', seo_code, "Must define verification endpoint handler.")
        self.assertIn('bingsiteauth.xml', seo_code.lower(), "Must handle /BingSiteAuth.xml endpoint.")
        self.assertIn('vg_dispatch_indexnow', seo_code, "Must define IndexNow dispatch function.")
        self.assertIn('api.indexnow.org', seo_code, "Must target api.indexnow.org.")
        self.assertIn('bing.com/indexnow', seo_code, "Must target bing.com/indexnow.")

        # 3. WebSub / PubSubHubbub pings
        self.assertIn('vg_dispatch_websub_pings', seo_code, "Must define WebSub pinger.")
        self.assertIn('pubsubhubbub.appspot.com', seo_code, "Must ping pubsubhubbub hub.")
        self.assertIn('superfeedr.com/hubbub', seo_code, "Must ping superfeedr hub.")

        # 4. Post publish transition hook with non-blocking & debounce
        self.assertIn('vg_handle_post_publish_discovery', seo_code, "Must define transition hook handler.")
        self.assertIn('transition_post_status', seo_code, "Must hook into transition_post_status.")
        self.assertIn('vg_ping_lock_', seo_code, "Must implement debounce lock transient.")
        self.assertIn("'blocking'    => false", seo_code, "Must enforce non-blocking HTTP dispatch for editorial speed.")
        self.assertIn('litespeed_purge_url', seo_code, "Must invalidate sitemap and feed cache upon publication.")

    def test_route_budget_widget_and_presets(self):
        """guide-cost-calculator.php must implement Route Presets and the [vg_route_budget] shortcode."""
        calc_code = self.contents['cost_calculator']

        # 1. Shortcode and render function
        self.assertIn("add_shortcode('vg_route_budget'", calc_code, "Must register [vg_route_budget] shortcode.")
        self.assertIn("function vg_render_route_budget_widget", calc_code, "Must define vg_render_route_budget_widget().")

        # 2. Route presets coverage
        self.assertIn("'ha-giang'", calc_code, "Must support ha-giang route preset.")
        self.assertIn("'da-nang-hoi-an'", calc_code, "Must support da-nang-hoi-an route preset.")
        self.assertIn("'hanoi-ninh-binh-halong'", calc_code, "Must support hanoi-ninh-binh-halong route preset.")
        self.assertIn("'classic-10d'", calc_code, "Must support classic-10d route preset.")

        # 3. Tactile route chips and route profile card
        self.assertIn('vg-calc-route-chips', calc_code, "Must render route preset chips wrapper.")
        self.assertIn('data-route="ha-giang"', calc_code, "Must render Ha Giang preset chip.")
        self.assertIn('data-route="da-nang-hoi-an"', calc_code, "Must render Da Nang & Hoi An preset chip.")
        self.assertIn('vg-calc-route-profile', calc_code, "Must render route profile verified costs card.")

        # 4. Route budget widget markup contract
        self.assertIn('vg-route-budget-widget', calc_code, "Widget must use .vg-route-budget-widget container.")
        self.assertIn('vg-rb-mode-toggle', calc_code, "Widget must provide mode toggle.")
        self.assertIn('vg-rb-currency-toggle', calc_code, "Widget must provide currency toggle.")
        self.assertIn('vg-rb-btn-cta', calc_code, "Widget must provide CTA deep-linking to full calculator.")

    def test_zero_h2_invariant_across_all_inc_files(self):
        """Zero-H2 Invariant must hold across all PHP files in inc/*.php to prevent TOC corruption."""
        inc_files = glob.glob(os.path.join(THEME_INC, '*.php'))
        self.assertGreaterEqual(len(inc_files), 17, f"Expected at least 17 PHP files in {THEME_INC}, found {len(inc_files)}")
        for php_path in sorted(inc_files):
            basename = os.path.basename(php_path)
            with open(php_path, 'r', encoding='utf-8') as f:
                code = f.read()
            h2_matches = re.findall(r'<h2\b', code, re.IGNORECASE)
            self.assertEqual(
                len(h2_matches),
                0,
                f"Zero-H2 violation in {basename}: found {len(h2_matches)} <h2> tags: {h2_matches}"
            )

    def test_all_theme_shortcodes_registered_via_functions_php(self):
        """All shortcodes defined across inc/*.php must be loaded via functions.php and prefixed with vg_."""
        functions_file = os.path.join(THEME_DIR, 'functions.php')
        self.assertTrue(os.path.isfile(functions_file), f"Missing {functions_file}")
        with open(functions_file, 'r', encoding='utf-8') as f:
            func_code = f.read()

        inc_files = glob.glob(os.path.join(THEME_INC, '*.php'))
        found_shortcodes = []
        for path in inc_files:
            rel_inc = '/inc/' + os.path.basename(path)
            with open(path, 'r', encoding='utf-8') as f:
                code = f.read()
            shortcodes = re.findall(r"add_shortcode\(\s*['\"]([^'\"]+)['\"]", code)
            if shortcodes:
                self.assertIn(
                    rel_inc,
                    func_code,
                    f"File {rel_inc} registers shortcode(s) {shortcodes} but is not required in functions.php"
                )
                found_shortcodes.extend(shortcodes)

        self.assertGreaterEqual(len(found_shortcodes), 10, "Expected at least 10 registered shortcodes")
        for sc in found_shortcodes:
            self.assertTrue(
                sc.startswith('vg_'),
                f"Shortcode [{sc}] must adhere to vg_ prefix convention"
            )

        expected_core = [
            'vg_interactive_map',
            'vg_photo_dispatch',
            'vg_visa_checker',
            'vg_season_matrix',
            'vg_cost_calculator',
            'vg_route_budget',
            'vg_itinerary_finder',
            'vg_airport_navigator',
            'vg_packing_checklist',
        ]
        for sc in expected_core:
            self.assertIn(sc, found_shortcodes, f"Expected core shortcode [{sc}] not found")

    def test_css_class_prefix_scoping(self):
        """Interactive shortcode components must scope their CSS classes with the 'vg-' prefix."""
        root_class_patterns = {
            'visa_checker': r'<section\s+class="([^"]*vg-visa-checker[^"]*)"',
            'season_matrix': r'<section\s+class="([^"]*vg-season-matrix[^"]*)"',
            'cost_calculator': r'<section\s+class="([^"]*vg-cost-calculator[^"]*)"',
            'itinerary_finder': r'<section\s+class="([^"]*vg-itinerary-finder[^"]*)"',
            'airport_navigator': r'<section\s+class="([^"]*vg-airport-navigator[^"]*)"',
            'packing_checklist': r'<section\s+class="([^"]*vg-checklist-widget[^"]*)"',
        }
        for key, pattern in root_class_patterns.items():
            self.assertRegex(
                self.contents[key],
                pattern,
                f"Component {key} must scope container with vg- prefixed class"
            )

        map_path = os.path.join(THEME_INC, 'guide-interactive-map.php')
        photo_path = os.path.join(THEME_INC, 'guide-photo-dispatch.php')
        with open(map_path, 'r', encoding='utf-8') as f:
            map_code = f.read()
        with open(photo_path, 'r', encoding='utf-8') as f:
            photo_code = f.read()

        self.assertIn('class="vg-interactive-map-desk"', map_code)
        self.assertIn('class="vg-photo-dispatch', photo_code)

    def test_front_page_hub_shortcodes_integration_and_headings(self):
        """front-page.php must integrate [vg_interactive_map] and [vg_photo_dispatch] with zero-H2 TOC integrity."""
        fp_path = os.path.join(THEME_DIR, 'front-page.php')
        self.assertTrue(os.path.isfile(fp_path), f"Missing {fp_path}")
        with open(fp_path, 'r', encoding='utf-8') as f:
            fp_content = f.read()

        self.assertIn('[vg_interactive_map]', fp_content, "front-page.php must embed [vg_interactive_map]")
        self.assertIn('[vg_photo_dispatch', fp_content, "front-page.php must embed [vg_photo_dispatch]")

        field_desk_section = re.search(r'<section[^>]*vg-field-desk-showcase[^>]*>(.*?)</section>', fp_content, re.DOTALL)
        self.assertIsNotNone(field_desk_section, "Field Desk section not found in front-page.php")
        self.assertIn('<h3', field_desk_section.group(1), "Field Desk section heading must use <h3>")
        self.assertNotIn('<h2', field_desk_section.group(1), "Field Desk section heading must NOT use <h2>")

    def test_expanded_toolkit_and_visual_auto_injection_coverage(self):
        """Interactive map, photo dispatch, and all 6 toolkits must auto-inject across expanded canonical routes."""
        map_path = os.path.join(THEME_INC, 'guide-interactive-map.php')
        photo_path = os.path.join(THEME_INC, 'guide-photo-dispatch.php')
        with open(map_path, 'r', encoding='utf-8') as f:
            map_code = f.read()
        with open(photo_path, 'r', encoding='utf-8') as f:
            photo_code = f.read()

        self.assertIn('vg_inject_interactive_map_on_hub_and_itineraries', map_code)
        self.assertIn("'10-days-in-vietnam'", map_code)
        self.assertIn("'north-central-south-vietnam'", map_code)

        self.assertIn('vg_inject_photo_dispatch_on_pillar_destinations', photo_code)
        self.assertIn("'ha-long-bay-travel-guide'", photo_code)
        self.assertIn("'hoi-an-ancient-town-guide'", photo_code)
        self.assertIn("'ha-giang-loop-planning-guide'", photo_code)

        self.assertIn("'vietnam-in-january'", self.contents['season_matrix'])
        self.assertIn("'vietnam-in-october'", self.contents['season_matrix'])
        self.assertIn("'hanoi-airport-to-old-quarter'", self.contents['airport_navigator'])
        self.assertIn("'14-days-in-vietnam'", self.contents['itinerary_finder'])
        self.assertIn("'money-cash-cards-atms'", self.contents['cost_calculator'])
        self.assertIn('vg_render_route_budget_widget', self.contents['cost_calculator'])

    def test_expanded_9_gateway_airport_navigator_ground_truth(self):
        """Airport navigator must cover all 9 major and regional gateways (HAN, SGN, DAD, CXR, PQC, HUI, DLI, VDH, HPH) with verified ground-truth transfers."""
        an_code = self.contents['airport_navigator']
        for code in ['HAN', 'SGN', 'DAD', 'CXR', 'PQC', 'HUI', 'DLI', 'VDH', 'HPH']:
            self.assertIn(f"'{code}' => [", an_code, f"Airport navigator must define gateway dataset for {code}")
        self.assertIn('Phu Bai International Airport (Terminal T2)', an_code)
        self.assertIn('Lien Khuong International Airport', an_code)
        self.assertIn('Lado Taxi', an_code)
        self.assertIn('Dong Hoi Airport', an_code)
        self.assertIn('Bus B4', an_code)
        self.assertIn('Cat Bi International Airport', an_code)
        self.assertIn('Dong Bai Ferry Terminal', an_code)

    def test_itinerary_finder_rail_and_aviation_reference_ledger(self):
        """Itinerary finder must include the 2025-2026 Verified Vietnam Railways (dsvn.vn) & Domestic Aviation Reference Ledger."""
        if_code = self.contents['itinerary_finder']
        self.assertIn('vg-finder-rail-air-ledger', if_code)
        self.assertIn('HĐ1', if_code)
        self.assertIn('HĐ2', if_code)
        self.assertIn('Lang Co Station', if_code)
        self.assertIn('SE3 departs Hanoi 19:20', if_code)
        self.assertIn('SP3 departs Hanoi Station', if_code)
        self.assertIn('Vietnam Airlines (VN)', if_code)
        self.assertIn('Vietjet Air (VJ)', if_code)

    def test_cluster_aware_photo_dispatch_auto_injection(self):
        """guide-photo-dispatch.php must resolve regional photo dispatches across all 12 travel clusters dynamically."""
        photo_path = os.path.join(THEME_INC, 'guide-photo-dispatch.php')
        with open(photo_path, 'r', encoding='utf-8') as f:
            photo_code = f.read()
        self.assertIn('vg_find_travel_cluster_by_path', photo_code)
        self.assertIn('$clusterToSeriesMap', photo_code)
        for cluster_id in [
            'northern_triangle',
            'ha_long_bay',
            'ninh_binh',
            'central_heritage',
            'southern_delta',
            'northern_highlands',
            'coastal_islands',
            'central_highlands',
            'phong_nha_caves',
            'mekong_delta',
            'far_north_frontier',
            'national_circuit',
        ]:
            self.assertIn(f"'{cluster_id}'", photo_code)

    def test_visa_checker_resolution_44_and_229_nq_cp_ground_truth(self):
        """Visa checker must reflect Government Resolutions 44/NQ-CP and 229/NQ-CP (2025-2028) and official MPS portals."""
        vc_code = self.contents['visa_checker']
        self.assertIn('Resolution 44/NQ-CP', vc_code)
        self.assertIn('Resolution 229/NQ-CP', vc_code)
        self.assertIn('https://thithucdientu.gov.vn/', vc_code)
        self.assertIn("'vietnam-to-cambodia-border-crossings'", vc_code)
        for iso_code in ['CH', 'NL', 'BE', 'PL', 'CZ', 'HU', 'LU', 'RO', 'SK', 'SI', 'BG', 'HR']:
            pattern = rf"'{iso_code}'\s*=>\s*\[[^\]]*'cat'\s*=>\s*'exemption_45'"
            self.assertRegex(
                vc_code,
                pattern,
                f"Country {iso_code} must be configured with exemption_45 under Resolution 229/NQ-CP"
            )

    def test_cost_calculator_monument_tariffs_and_route_budget_expansion(self):
        """Cost calculator must include HCMC & Trang An 2026 tariffs and expanded route budget auto-injection."""
        cc_code = self.contents['cost_calculator']
        self.assertIn('War Remnants Museum / Independence Palace / Cu Chi Tunnels (Ben Dinh)', cc_code)
        self.assertIn('40,000 / 40,000–65,000 / 125,000 VND', cc_code)
        self.assertIn('Trang An 250,000–300,000 VND', cc_code)
        for slug in [
            'tipping-in-vietnam',
            'ha-giang-easy-rider-vs-self-drive',
            'central-vietnam-itinerary',
            'ha-long-bay-day-trip-vs-overnight-cruise',
            '7-days-in-vietnam',
        ]:
            self.assertIn(f"'{slug}'", cc_code)

    def test_packing_checklist_electrical_battery_pharmacy_ledger(self):
        """Packing checklist must include the 2025-2026 Electrical Grid (220V/50Hz), CAAV Battery & Pharmacy Reference Ledger."""
        pc_code = self.contents['packing_checklist']
        self.assertIn('vg-checklist-field-specs', pc_code)
        self.assertIn('220V / 50Hz (Type A, C &amp; F)', pc_code)
        self.assertIn('100Wh (~27,000mAh at 3.7V)', pc_code)
        self.assertIn('Circular 35/2017/TT-BQP', pc_code)
        self.assertIn('FPT Long Châu / Pharmacity / 113–115', pc_code)
        for slug in [
            'vietnam-plug-adapter-electricity-guide',
            'vietnam-sim-card-airport-vs-city',
            'vietnam-scooter-rental-checklist',
        ]:
            self.assertIn(f"'{slug}'", pc_code)


if __name__ == '__main__':
    unittest.main()
