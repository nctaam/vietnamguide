# -*- coding: utf-8 -*-
"""
VietnamGuide 4-Tier Opaque-Box E2E Linguistic & Anti-AI Slop Test Suite.

Authoritative Sources:
- docs/editorial/anti-ai-slop-style-guide.md (Anti-AI Slop Constitution)
- ops/anti_ai_slop_linter.py (Anti-AI Slop Linter & Quality Engine v12.0)
- ops/route_registry.json & inc/guide-route-registry.json (282 routes)
- ORIGINAL_REQUEST.md & PROJECT.md (Interface Contracts & Constraints)

Tiers:
- Tier 1: Feature Coverage (0 Tier 1 Clichés on 282 routes, Homepage, 6 Toolkits, Utility pages, 13 Patterns)
- Tier 2: Boundary & Corner Cases (Empty strings, UTF-8 diacritics, extreme lengths, VND currency formats)
- Tier 3: Cross-Feature Combinations (Place name consistency, official E-Visa URL, SHA-256 parity, frozen contracts)
- Tier 4: Real-World Scenarios & Quality Thresholds (HLS score >= 85, factual density: distance, transit, pricing, climate)
"""

import os
import sys
import json
import re
import hashlib
import unittest
from pathlib import Path

# Path configuration
REPO_ROOT = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..')))
OPS_DIR = REPO_ROOT / 'ops'
THEME_DIR = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium'

if str(OPS_DIR) not in sys.path:
    sys.path.insert(0, str(OPS_DIR))

try:
    import anti_ai_slop_linter as linter
except ImportError:
    linter = None


def extract_clean_text_from_php(content: str) -> str:
    """Strip PHP tags and extract visible UI copy and gettext strings cleanly."""
    no_php = re.sub(r'<\?php.*?\?>', ' ', content, flags=re.DOTALL)
    gettext_strings = re.findall(r'''(?:__|esc_html__|esc_attr__|_e)\(\s*['"](.*?)['"]\s*,''', content)
    combined = no_php + " " + " ".join(gettext_strings)
    if linter:
        return linter.strip_html(combined)
    # Fallback tag stripping if linter is not loaded
    clean = re.sub(r'<[^>]+>', ' ', combined)
    return re.sub(r'\s+', ' ', clean).strip()


# ==============================================================================
# TIER 1: FEATURE COVERAGE (ZERO TIER 1 CLICHÉS)
# ==============================================================================

class TestTier1FeatureCoverage(unittest.TestCase):
    """
    Tier 1 tests verify complete absence of Tier 1 banned clichés across all
    content surfaces: 282 Route Registry titles & descriptions, Homepage,
    6 Interactive Toolkits, Utility templates, and 13 Gutenberg Patterns.
    """

    def setUp(self):
        self.assertIsNotNone(linter, "Could not import anti_ai_slop_linter module")
        self.registry_path = OPS_DIR / 'route_registry.json'
        self.assertTrue(self.registry_path.is_file(), f"Route registry not found: {self.registry_path}")
        with open(self.registry_path, 'r', encoding='utf-8') as f:
            self.registry_data = json.load(f)
        self.routes = self.registry_data.get('routes', [])

    def test_tier1_all_282_route_titles_and_descriptions_cliche_free(self):
        """Verify 0 Tier 1 clichés on all 282 route titles and descriptions."""
        self.assertEqual(len(self.routes), 282, f"Expected exactly 282 routes, found {len(self.routes)}")

        violations = []
        for route in self.routes:
            path = route.get('path', 'unknown')
            title = route.get('title', '')
            desc = route.get('description', '')

            full_text = f"{title}. {desc}"
            report = linter.analyze_text(full_text, source_name=path)

            if report['tier1_count'] > 0:
                violations.append({
                    'path': path,
                    'violations': report['tier1_violations']
                })

        self.assertEqual(
            len(violations), 0,
            f"Found Tier 1 cliché violations in {len(violations)} routes: {violations[:3]}"
        )

    def test_tier1_theme_bundled_route_registry_cliche_free(self):
        """Verify 0 Tier 1 clichés on theme-bundled guide-route-registry.json."""
        inc_registry_path = THEME_DIR / 'inc' / 'guide-route-registry.json'
        self.assertTrue(inc_registry_path.is_file(), f"Theme registry not found: {inc_registry_path}")

        with open(inc_registry_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        routes = data.get('routes', [])
        self.assertEqual(len(routes), 282, "Theme bundle must contain exactly 282 routes")

        violations = []
        for route in routes:
            text = f"{route.get('title', '')}. {route.get('description', '')}"
            report = linter.analyze_text(text, source_name=route.get('path', ''))
            if report['tier1_count'] > 0:
                violations.append((route.get('path', ''), report['tier1_violations']))

        self.assertEqual(len(violations), 0, f"Theme registry has Tier 1 violations: {violations}")

    def test_tier1_homepage_ui_templates_cliche_free(self):
        """Verify 0 Tier 1 clichés across all Homepage template files."""
        homepage_files = [
            THEME_DIR / 'front-page.php',
            THEME_DIR / 'header.php',
            THEME_DIR / 'footer.php',
            THEME_DIR / 'inc' / 'homepage-data.php',
        ]

        for file_path in homepage_files:
            self.assertTrue(file_path.is_file(), f"Homepage file missing: {file_path}")
            content = file_path.read_text(encoding='utf-8', errors='ignore')
            clean_text = extract_clean_text_from_php(content)
            report = linter.analyze_text(clean_text, source_name=file_path.name)
            self.assertEqual(
                report['tier1_count'], 0,
                f"Tier 1 violations in {file_path.name}: {report['tier1_violations']}"
            )

    def test_tier1_six_interactive_toolkits_cliche_free(self):
        """Verify 0 Tier 1 clichés across all 6 Interactive Travel Toolkits."""
        toolkit_files = [
            THEME_DIR / 'inc' / 'guide-visa-checker.php',
            THEME_DIR / 'inc' / 'guide-cost-calculator.php',
            THEME_DIR / 'inc' / 'guide-season-matrix.php',
            THEME_DIR / 'inc' / 'guide-airport-navigator.php',
            THEME_DIR / 'inc' / 'guide-packing-checklist.php',
            THEME_DIR / 'inc' / 'guide-itinerary-finder.php',
        ]

        for tk_path in toolkit_files:
            self.assertTrue(tk_path.is_file(), f"Toolkit missing: {tk_path}")
            content = tk_path.read_text(encoding='utf-8', errors='ignore')
            clean_text = extract_clean_text_from_php(content)
            report = linter.analyze_text(clean_text, source_name=tk_path.name)
            self.assertEqual(
                report['tier1_count'], 0,
                f"Tier 1 violations in {tk_path.name}: {report['tier1_violations']}"
            )

    def test_tier1_utility_pages_cliche_free(self):
        """Verify 0 Tier 1 clichés on Search, 404, and Offline PWA templates."""
        utility_files = [
            THEME_DIR / 'search.php',
            THEME_DIR / '404.php',
            REPO_ROOT / 'wordpress' / 'offline.html',
        ]

        for ut_path in utility_files:
            self.assertTrue(ut_path.is_file(), f"Utility page missing: {ut_path}")
            content = ut_path.read_text(encoding='utf-8', errors='ignore')
            clean_text = extract_clean_text_from_php(content) if ut_path.suffix == '.php' else linter.strip_html(content)
            report = linter.analyze_text(clean_text, source_name=ut_path.name)
            self.assertEqual(
                report['tier1_count'], 0,
                f"Tier 1 violations in {ut_path.name}: {report['tier1_violations']}"
            )

    def test_tier1_gutenberg_block_patterns_cliche_free(self):
        """Verify 0 Tier 1 clichés across all 13 Gutenberg Block Patterns."""
        patterns_dir = THEME_DIR / 'patterns'
        self.assertTrue(patterns_dir.is_dir(), f"Patterns directory missing: {patterns_dir}")
        pattern_files = list(patterns_dir.glob('*.php'))
        self.assertEqual(len(pattern_files), 13, f"Expected 13 Gutenberg patterns, found {len(pattern_files)}")

        for pat_path in pattern_files:
            content = pat_path.read_text(encoding='utf-8', errors='ignore')
            clean_text = extract_clean_text_from_php(content)
            report = linter.analyze_text(clean_text, source_name=pat_path.name)
            self.assertEqual(
                report['tier1_count'], 0,
                f"Tier 1 violations in pattern {pat_path.name}: {report['tier1_violations']}"
            )


# ==============================================================================
# TIER 2: BOUNDARY & CORNER CASES
# ==============================================================================

class TestTier2BoundaryAndCornerCases(unittest.TestCase):
    """
    Tier 2 tests verify resilience across edge-case inputs: empty strings,
    Vietnamese diacritics / non-ASCII UTF-8 characters, extreme lengths,
    and numerical / currency formatting conventions.
    """

    def setUp(self):
        self.assertIsNotNone(linter, "Could not import anti_ai_slop_linter module")

    def test_tier2_empty_string_and_whitespace_handling(self):
        """Verify empty and whitespace inputs evaluate gracefully without errors."""
        empty_cases = ["", "   ", "\t\t", "\n\n\r", "   \n\t  \n"]
        for case in empty_cases:
            report = linter.analyze_text(case, source_name="empty_test")
            self.assertTrue(report['passed'], f"Empty string should pass analysis: {repr(case)}")
            self.assertEqual(report['tier1_count'], 0)
            self.assertEqual(report['word_count'], 0)

    def test_tier2_non_ascii_and_vietnamese_diacritics_preservation(self):
        """Verify Vietnamese diacritics and typography are preserved without mangling."""
        diacritics_samples = [
            "Đi từ Hà Nội đến Đà Nẵng bằng tàu SE1 mất 16 giờ, giá vé 950,000 VND một lượt.",
            "Tại TP. Hồ Chí Minh, triều cường gây ngập đường vào mùa mưa tháng 9–10.",
            "Đèo Hải Vân nối Thừa Thiên Huế và Đà Nẵng qua Quốc lộ 1A với độ dài 21 km.",
            "Thời tiết miền Bắc có hiện tượng nồm ẩm (độ ẩm 85–95%) vào tháng 2–3.",
        ]

        for sample in diacritics_samples:
            report = linter.analyze_text(sample, source_name="diacritics_test")
            self.assertEqual(report['tier1_count'], 0)
            self.assertGreaterEqual(report['hls_score'], 80)
            # Ensure text length and words are counted accurately
            self.assertGreater(report['word_count'], 5)

    def test_tier2_extreme_string_lengths(self):
        """Verify performance and stability on very short and very long strings."""
        # Ultra-short inputs
        short_inputs = ["Hanoi", "Noi Bai Airport", "Da Nang Express Bus"]
        for s in short_inputs:
            report = linter.analyze_text(s, source_name="short_test")
            self.assertTrue(report['passed'])
            self.assertEqual(report['tier1_count'], 0)
            self.assertEqual(report['cv'], 0.5)  # Neutral default for < 3 sentences

        # Long input: 800+ words
        sentence = "Travel from Hanoi to Ninh Binh spans 95 km along National Route 1A, taking 90 minutes by limousine bus (150,000 VND). "
        long_text = sentence * 45
        report_long = linter.analyze_text(long_text, source_name="long_test")
        self.assertGreater(report_long['word_count'], 800)
        self.assertEqual(report_long['tier1_count'], 0)
        self.assertGreater(report_long['evidence_count'], 10)

    def test_tier2_numerical_and_currency_formatting_standards(self):
        """Verify standard Vietnamese Dong formatting conventions (e.g. VND 25,500)."""
        # Formats: 25,500 VND, VND 25,500, ranges with en-dash
        test_strings = [
            ("Standard fare is 25,500 VND per ticket.", 1),
            ("Official exchange rate: 25,500 VND to 1 USD.", 1),
            ("GrabCar costs 280,000–320,000 VND from Noi Bai.", 1),
            ("Train tickets range from 450,000 VND to 950,000 VND.", 2),
            ("Visa processing fee is $25 USD.", 1),
        ]

        for text, expected_min_currencies in test_strings:
            matches = list(linter.CURRENCY_REGEX.finditer(text))
            self.assertGreaterEqual(
                len(matches), expected_min_currencies,
                f"Failed to detect currency in: '{text}'"
            )

        # Verify FX rate calibration in Cost Calculator toolkit
        cost_calc_path = THEME_DIR / 'inc' / 'guide-cost-calculator.php'
        self.assertTrue(cost_calc_path.is_file())
        cost_content = cost_calc_path.read_text(encoding='utf-8')
        self.assertIn("USD_TO_VND = 25500", cost_content, "Cost calculator FX rate must be calibrated to 25,500 VND")


# ==============================================================================
# TIER 3: CROSS-FEATURE COMBINATIONS & INVARIANTS
# ==============================================================================

class TestTier3CrossFeatureTerminologyAndUrlConsistency(unittest.TestCase):
    """
    Tier 3 tests verify cross-system contracts and consistency: place name
    conventions across UI and Route Registry, official E-Visa portal URL
    integrity, SHA-256 byte parity, and frozen route contracts.
    """

    def setUp(self):
        self.assertIsNotNone(linter)
        self.ops_registry_path = OPS_DIR / 'route_registry.json'
        self.inc_registry_path = THEME_DIR / 'inc' / 'guide-route-registry.json'

    def test_tier3_geographical_place_name_consistency_in_routes(self):
        """Verify no unspaced forbidden forms (Danang, Dalat, Hoian, Halong Bay) in route titles."""
        with open(self.ops_registry_path, 'r', encoding='utf-8') as f:
            registry = json.load(f)

        forbidden_patterns = [
            (r'\bHalong(?!\s*International)\b', 'Ha Long'),
            (r'\bDanang\b', 'Da Nang'),
            (r'\bDalat\b', 'Da Lat'),
            (r'\bHoian\b', 'Hoi An'),
            (r'\bNhatrang\b', 'Nha Trang'),
            (r'\bPhuquoc\b', 'Phu Quoc'),
        ]

        title_issues = []
        desc_issues = []
        for r in registry.get('routes', []):
            title = r.get('title', '')
            desc = r.get('description', '')
            for pat, canonical in forbidden_patterns:
                if re.search(pat, title, re.IGNORECASE):
                    title_issues.append((r.get('path', ''), title, canonical))
                # For descriptions, exclude 'Halong International' port proper noun
                if re.search(pat, desc, re.IGNORECASE):
                    desc_issues.append((r.get('path', ''), desc, canonical))

        self.assertEqual(len(title_issues), 0, f"Forbidden place names in route titles: {title_issues}")
        self.assertEqual(len(desc_issues), 0, f"Forbidden place names in route descriptions: {desc_issues}")

    def test_tier3_canonical_place_names_present_across_registry(self):
        """Verify canonical place names exist and are consistently formatted in registry."""
        with open(self.ops_registry_path, 'r', encoding='utf-8') as f:
            registry = json.load(f)

        all_text = " ".join([f"{r.get('title', '')} {r.get('description', '')}" for r in registry.get('routes', [])])

        canonical_destinations = [
            'Ha Long Bay',
            'Da Nang',
            'Da Lat',
            'Hoi An',
            'Ho Chi Minh City',
            'Saigon',
            'Sapa',
        ]

        for place in canonical_destinations:
            count = len(re.findall(r'\b' + re.escape(place) + r'\b', all_text))
            self.assertGreater(
                count, 0,
                f"Expected canonical destination '{place}' to appear in route registry, found 0"
            )

    def test_tier3_official_evisa_portal_url_consistency(self):
        """Verify official E-Visa URL is evisa.xuatnhapcanh.gov.vn and no commercial agencies linked."""
        # 1. Check Visa Checker toolkit
        visa_checker_path = THEME_DIR / 'inc' / 'guide-visa-checker.php'
        self.assertTrue(visa_checker_path.is_file())
        visa_content = visa_checker_path.read_text(encoding='utf-8')
        self.assertIn(
            'https://evisa.xuatnhapcanh.gov.vn/',
            visa_content,
            "Visa Checker component must point to official portal https://evisa.xuatnhapcanh.gov.vn/"
        )

        # 2. Check AIO integration
        aio_path = THEME_DIR / 'inc' / 'guide-aio.php'
        if aio_path.is_file():
            aio_content = aio_path.read_text(encoding='utf-8')
            self.assertIn('evisa.xuatnhapcanh.gov.vn', aio_content)

        # 3. Check Linter regulatory hotline pattern recognizes xuatnhapcanh
        self.assertTrue(
            bool(linter.OPERATOR_HOTLINE_REGEX.search("evisa.xuatnhapcanh.gov.vn")),
            "Linter hotline regex must recognize official immigration domain evisa.xuatnhapcanh.gov.vn"
        )

        # 4. Check Gutenberg source block links to official government portal (.gov.vn)
        source_block_path = THEME_DIR / 'patterns' / 'source-block.php'
        self.assertTrue(source_block_path.is_file())
        source_content = source_block_path.read_text(encoding='utf-8')
        self.assertTrue(
            bool(re.search(r'https://(?:evisa\.xuatnhapcanh\.gov\.vn|evisa\.gov\.vn)/', source_content)),
            "Source block pattern must link to an official Vietnamese government e-visa portal (.gov.vn)"
        )

        # 5. Verify no scam or commercial third-party visa agencies
        scam_domains = ['vietnamvisa.com', 'myvietnamvisa.com', 'vietnam-visa.com', 'vietnamvisa-online.org']
        for root, _, files in os.walk(THEME_DIR):
            for f in files:
                if f.endswith(('.php', '.html')):
                    c = (Path(root) / f).read_text(encoding='utf-8', errors='ignore')
                    for scam in scam_domains:
                        self.assertNotIn(scam, c, f"Found commercial visa agency '{scam}' in {f}")

    def test_tier3_route_registry_sha256_byte_parity(self):
        """Verify 100% byte-for-byte SHA-256 synchronization between ops and inc route registries."""
        self.assertTrue(self.ops_registry_path.is_file())
        self.assertTrue(self.inc_registry_path.is_file())

        ops_bytes = self.ops_registry_path.read_bytes()
        inc_bytes = self.inc_registry_path.read_bytes()

        ops_hash = hashlib.sha256(ops_bytes).hexdigest()
        inc_hash = hashlib.sha256(inc_bytes).hexdigest()

        self.assertEqual(
            ops_hash, inc_hash,
            f"Registry drift detected! SHA-256 mismatch:\n  ops: {ops_hash}\n  inc: {inc_hash}"
        )

    def test_tier3_frozen_contracts_preserved(self):
        """Verify frozen contracts: Route 1 verbatim title and total route count 282."""
        with open(self.ops_registry_path, 'r', encoding='utf-8') as f:
            registry = json.load(f)

        routes = registry.get('routes', [])
        self.assertEqual(len(routes), 282, "Total route count must remain frozen at exactly 282")

        # Frozen contract: Route 1 ('plan/vietnam-evisa')
        evisa_routes = [r for r in routes if r.get('path') == 'plan/vietnam-evisa']
        self.assertEqual(len(evisa_routes), 1, "Route 'plan/vietnam-evisa' must exist in registry")
        evisa_route = evisa_routes[0]
        self.assertEqual(
            evisa_route.get('title'),
            'Vietnam E-Visa Guide: Official Portal, Fees and Mistakes',
            "Route 1 title must match frozen contract verbatim"
        )


# ==============================================================================
# TIER 4: REAL-WORLD APPLICATION SCENARIOS & QUALITY THRESHOLDS
# ==============================================================================

class TestTier4RealWorldScenariosAndQualityThresholds(unittest.TestCase):
    """
    Tier 4 tests verify editorial-grade human-likeness quality thresholds
    (HLS score >= 85), high ground-truth factual density across routes,
    and complete absence of extended AI tropes.
    """

    def setUp(self):
        self.assertIsNotNone(linter)
        self.registry_path = OPS_DIR / 'route_registry.json'
        with open(self.registry_path, 'r', encoding='utf-8') as f:
            self.routes = json.load(f).get('routes', [])

    def test_tier4_hls_score_threshold_on_all_282_routes(self):
        """Verify every route in the registry achieves HLS score >= 85."""
        low_hls_routes = []
        scores = []
        for r in self.routes:
            path = r.get('path', '')
            text = f"{r.get('title', '')}. {r.get('description', '')}"
            report = linter.analyze_text(text, source_name=path)
            score = report['hls_score']
            scores.append(score)
            if score < 85:
                low_hls_routes.append((path, score))

        self.assertEqual(
            len(low_hls_routes), 0,
            f"{len(low_hls_routes)} routes failed HLS >= 85 threshold: {low_hls_routes[:5]}"
        )
        avg_score = sum(scores) / len(scores) if scores else 0
        self.assertGreaterEqual(avg_score, 90.0, f"Average route HLS score should exceed 90.0, got {avg_score:.2f}")

    def test_tier4_ground_truth_factual_density_in_route_descriptions(self):
        """Verify ground-truth operational metrics (distance, transit, pricing, climate) exist across routes."""
        has_distance = 0
        has_transit_time = 0
        has_pricing = 0
        has_climate = 0

        for r in self.routes:
            desc = r.get('description', '')

            # Distance metrics
            if any(k in desc.lower() for k in ['km', 'm ', 'meters', 'altitude', 'distance']):
                has_distance += 1

            # Transit metrics
            if linter.TRANSIT_TIME_REGEX.search(desc) or any(k in desc.lower() for k in ['hour', 'minute', 'day', 'train', 'bus', 'flight', 'ferry']):
                has_transit_time += 1

            # Pricing metrics
            if linter.CURRENCY_REGEX.search(desc) or any(k in desc.lower() for k in ['vnd', 'cost', 'fare', 'price', 'fee', 'budget', '$', 'rate']):
                has_pricing += 1

            # Climate metrics
            if linter.CLIMATE_REGEX.search(desc) or any(k in desc.lower() for k in ['weather', 'season', 'rain', 'monsoon', 'typhoon', 'temp', 'humidity']):
                has_climate += 1

        # Check significant penetration of factual metrics across 282 routes
        self.assertGreater(has_distance, 50, f"Expected > 50 routes with distance metrics, found {has_distance}")
        self.assertGreater(has_transit_time, 100, f"Expected > 100 routes with transit metrics, found {has_transit_time}")
        self.assertGreater(has_pricing, 100, f"Expected > 100 routes with pricing metrics, found {has_pricing}")
        self.assertGreater(has_climate, 50, f"Expected > 50 routes with climate metrics, found {has_climate}")

    def test_tier4_absence_of_extended_ai_slop_tropes(self):
        """Verify 0 instances of extended AI slop tropes (Tiers 5, 7, 8, 9, 10, 11, 12)."""
        extended_violations = []
        for r in self.routes:
            path = r.get('path', '')
            text = f"{r.get('title', '')}. {r.get('description', '')}"
            report = linter.analyze_text(text, source_name=path)

            total_extended = (
                report['tier5_count'] +
                report['tier7_count'] +
                report['tier8_count'] +
                report['tier9_count'] +
                report['tier10_count'] +
                report['tier11_count'] +
                report['tier12_count']
            )

            if total_extended > 0:
                extended_violations.append((path, total_extended))

        self.assertEqual(
            len(extended_violations), 0,
            f"Found extended slop violations in {len(extended_violations)} routes: {extended_violations[:5]}"
        )


if __name__ == '__main__':
    unittest.main()
