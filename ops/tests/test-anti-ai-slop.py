# -*- coding: utf-8 -*-
"""
Unit tests for VietnamGuide Anti-AI Slop Linter & Metric Engine.
"""
import os
import sys
import unittest

# Add ops directory to sys.path
ops_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if ops_dir not in sys.path:
    sys.path.insert(0, ops_dir)

try:
    import anti_ai_slop_linter as linter
except ImportError:
    linter = None


class TestAntiAiSlopLinter(unittest.TestCase):

    def setUp(self):
        if linter is None:
            self.fail("Could not import anti_ai_slop_linter module")

    def test_tier1_banned_cliche_detection(self):
        sample_slop = (
            "Nestled in the heart of northern Vietnam, Hanoi is a land of contrasts. "
            "Whether you're a foodie or history buff, this bustling metropolis meets ancient charm. "
            "It is a testament to the rich tapestry of Vietnamese tradition. Look no further than the Old Quarter. "
            "In conclusion, Hanoi is an unforgettable journey and a must-visit destination."
        )
        report = linter.analyze_text(sample_slop)
        
        self.assertGreater(len(report['tier1_violations']), 0, "Should detect multiple Tier 1 violations")
        detected_phrases = [v['phrase'].lower() for v in report['tier1_violations']]
        self.assertTrue(any('nestled in the heart of' in p for p in detected_phrases))
        self.assertTrue(any('bustling metropolis' in p for p in detected_phrases))
        self.assertTrue(any('tapestry' in p for p in detected_phrases))
        self.assertTrue(any('in conclusion' in p for p in detected_phrases))
        self.assertFalse(report['passed'], "Sample with Tier 1 clichés must fail")

    def test_clean_human_evidence_text_passes(self):
        clean_text = (
            "Hanoi rarely fails because a traveler misses one famous temple. "
            "It fails when the base, arrival time, and weather window are decided in the wrong order. "
            "If you land at Noi Bai Airport after 21:00, book GrabCar for 280,000–320,000 VND to Hoan Kiem. "
            "Do not accept unmetered street solicitors inside Terminal 2 who quote 700,000 VND. "
            "Express Bus 86 costs 45,000 VND and runs every 45 minutes from Pillar 2. "
            "Under Resolution 128/NQ-CP, UK and German citizens get 45 days visa-free entry. "
            "For stays exceeding 45 days, secure a 90-day E-visa ($25 USD) before boarding."
        )
        report = linter.analyze_text(clean_text)
        
        self.assertEqual(len(report['tier1_violations']), 0, "Clean text should have 0 Tier 1 violations")
        self.assertGreaterEqual(report['hls_score'], 80, f"Clean text should score >= 80 HLS, got {report['hls_score']}")
        self.assertGreaterEqual(report['evidence_count'], 4, "Should detect multiple evidence anchors")
        self.assertTrue(report['passed'], "Clean, evidence-rich text must pass")

    def test_sentence_length_cadence_variation(self):
        # Monotonic robotic sentences (all ~12 words)
        robotic_text = (
            "The capital city of Vietnam offers many cultural sights for international tourists. "
            "You can visit many ancient temples and historic pagodas around the lake. "
            "The street food stalls serve delicious noodle soup with fresh green herbs. "
            "You should always remember to carry local cash for small morning purchases. "
            "The weather in the summer is hot and humid for most travelers."
        )
        robotic_report = linter.analyze_text(robotic_text)
        
        # Varied human cadence (short punchy assertions + complex analytical structures)
        human_text = (
            "Hanoi demands patience. "
            "When crossing Dinh Tien Hoang during afternoon rush hour, maintain a constant walking pace and hold eye contact with oncoming scooters. "
            "Never step backward. "
            "Motorbike drivers predict your forward vector, so any sudden stutter creates collision hazards."
        )
        human_report = linter.analyze_text(human_text)
        
        self.assertLess(robotic_report['cv'], 0.35, "Robotic text should have low sentence CV")
        self.assertGreaterEqual(human_report['cv'], 0.45, "Human text should have high sentence CV (>= 0.45)")

    def test_evidence_token_extraction(self):
        text_with_data = (
            "Fares range from 150,000 VND to 350,000 VND. "
            "The 120 km journey takes 2.5 hours by expressway limousine from My Dinh bus station. "
            "The official E-visa portal charges $25 USD under Decree 127."
        )
        report = linter.analyze_text(text_with_data)
        
        self.assertGreaterEqual(report['evidence']['currency_count'], 2)
        self.assertGreaterEqual(report['evidence']['transit_time_count'], 2)
        self.assertGreaterEqual(report['evidence']['regulatory_count'], 1)

    def test_tier1_expanded_subtle_cliches(self):
        subtle_slop = (
            "Ha Long Bay is truly a paradise for nature lovers. "
            "It is the crown jewel of northern Vietnam and a stone's throw away from Cat Ba. "
            "An unforgettable adventure awaits as you unravel the secrets of the bay. "
            "Whether you seek relaxation or simply stunning landscapes, it embodies the spirit of discovery. "
            "Let's delve into what makes this scenic wonder special."
        )
        report = linter.analyze_text(subtle_slop)
        self.assertGreaterEqual(len(report['tier1_violations']), 4, "Should catch subtle marketing and AI clichés")
        self.assertFalse(report['passed'], "Subtle slop must fail")

    def test_evidence_density_index_edi(self):
        # 500 words with zero evidence -> low EDI
        filler_sentence = "This destination has ancient streets with lanterns and pleasant breezes along the peaceful riverside. "
        low_evidence_text = filler_sentence * 35  # ~500 words
        report_low = linter.analyze_text(low_evidence_text)
        self.assertIn('edi', report_low, "Report must include Evidence Density Index (EDI)")
        self.assertLess(report_low['edi'], 4.0, "Low evidence text must have EDI < 4.0")
        self.assertFalse(report_low['passed'], "Long article with EDI < 4.0 must fail")

        # Text with high evidence density
        high_evidence_text = (
            "From Ga Hanoi, train SE3 departs at 19:20 and arrives in Hue at 08:30 (ticket 680,000 VND for soft sleeper 4-berth). "
            "Admission to Hue Imperial City is 200,000 VND per adult. "
            "Bus 86 from Noi Bai costs 45,000 VND, while GrabCar costs 300,000 VND to Hoan Kiem. "
            "Under Resolution 128/NQ-CP, visitors receive a 45-day exemption or can apply for a $25 USD 90-day e-visa. "
            "The 130 km expressway journey takes 2.0 hours."
        )
        report_high = linter.analyze_text(high_evidence_text)
        self.assertGreaterEqual(report_high['edi'], 10.0, "High evidence text must have high EDI")
        self.assertTrue(report_high['passed'], "High evidence text without clichés must pass")

    def test_tier3_structural_signposting(self):
        signposting_text = (
            "First and foremost, planning a trip to Vietnam requires proper timing. "
            "Whether you are a budget backpacker or luxury traveler, the country has something for everyone. "
            "Without further ado, let us look at the regional highlights. "
            "It is important to remember that seasons differ between north and south. "
            "All in all, Vietnam is a wonderful place to visit."
        )
        report = linter.analyze_text(signposting_text)
        self.assertIn('tier3_violations', report, "Report must include tier3_violations")
        self.assertGreaterEqual(len(report['tier3_violations']), 3, "Must catch formulaic signposting structures")
        self.assertFalse(report['passed'], "Text heavily packed with structural signposting must fail")

    def test_passive_voice_padding(self):
        passive_text = (
            "Visitors are treated to scenic mountain views upon arrival in Sapa. "
            "It is recommended that one takes a local trekking guide for safety. "
            "Travelers will find that the traditional ethnic villages offer handcrafted souvenirs. "
            "One can easily explore the surrounding valleys on a rented motorbike."
        )
        report = linter.analyze_text(passive_text)
        self.assertIn('passive_violations', report, "Report must include passive_violations")
        self.assertGreaterEqual(len(report['passive_violations']), 2, "Must detect detached passive observer phrasing")

    def test_expanded_evidence_patterns(self):
        detailed_evidence_text = (
            "Vietcombank ATMs enforce a 2,000,000 VND withdrawal limit per transaction with a 50,000 VND local fee, "
            "whereas BIDV permits up to 5,000,000 VND. "
            "For rail travel between Hanoi and Da Nang, choose the 4-berth soft sleeper on SE1 rather than 6-berth hard sleeper. "
            "In emergencies, contact SOS International Hanoi at 024.3934.0666 or the National Tourist Police hotline 113. "
            "Always submit your visa application directly via the official portal xuatnhapcanh.gov.vn."
        )
        report = linter.analyze_text(detailed_evidence_text)
        self.assertGreaterEqual(report['evidence']['currency_count'], 2, "Must detect VND withdrawal limits and fee numbers")
        self.assertGreaterEqual(report['evidence']['operator_count'], 2, "Must detect emergency hotlines and official portals")
        self.assertGreaterEqual(report['evidence_count'], 5, "Must recognize specific transit and operational evidence")
        self.assertTrue(report['passed'], "Evidence-dense operational guidance must pass")


if __name__ == '__main__':
    unittest.main()
