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

    def test_strict_edi_threshold_v4(self):
        # 500-word article with weak evidence (only 1 currency anchor -> EDI ~2.0) must FAIL in v4
        text = (
            "This travel guide explores the historical landmarks of northern Vietnam with careful attention to culture. "
            "Visitors can stroll along ancient corridors and witness local traditions in every village corner. "
        ) * 15 + "Tickets cost 100,000 VND at the gate. "
        report = linter.analyze_text(text)
        self.assertLess(report['edi'], 4.0)
        self.assertFalse(report['passed'], "In-depth guide with EDI < 4.0 must FAIL under v4 rules")

    def test_tier4_sycophancy_and_modern_ai_tropes(self):
        trope_text = (
            "It is worth delving into how this creates a truly memorable tapestry. "
            "Whether you are looking to embark on an adventure, rest assured that this vibrant hub has you covered. "
            "To say that the food is good is an understatement. "
            "Without a doubt, it goes without saying that Vietnam leaves an indelible mark."
        )
        report = linter.analyze_text(trope_text)
        self.assertIn('tier4_violations', report)
        self.assertGreaterEqual(len(report['tier4_violations']), 2, "Must detect Tier 4 sycophancy and conversational filler")
        self.assertFalse(report['passed'], "Tier 4 conversational filler must fail")

    def test_tier5_empty_superlatives_and_travel_fluff(self):
        fluff_text = (
            "Hanoi is a culinary delight and a foodie paradise that bursts with flavor. "
            "The ancient landscape is a sight to behold, offering unmatched beauty. "
            "This destination will leave you in awe and is truly something special."
        )
        report = linter.analyze_text(fluff_text)
        self.assertIn('tier5_violations', report)
        self.assertGreaterEqual(len(report['tier5_violations']), 3, "Must detect Tier 5 empty superlatives and travel fluff")
        self.assertFalse(report['passed'], "Tier 5 travel fluff must fail strict quality gate")

    def test_repetitive_sentence_opener_detection(self):
        repetitive_text = (
            "The morning market opens at dawn along the river. "
            "The vendors arrange fresh dragon fruit and herbs. "
            "The wooden boats glide quietly through the mist. "
            "The tourists arrive around eight in the morning."
        )
        report = linter.analyze_text(repetitive_text)
        self.assertIn('repetitive_openers_violations', report)
        self.assertGreaterEqual(len(report['repetitive_openers_violations']), 1, "Must detect 3+ consecutive sentences with identical opener")
        self.assertLess(report['hls_score'], 100, "Repetitive sentence openers must trigger a cadence penalty")

    def test_tier6_over_explanation_and_meta_commentary(self):
        meta_slop = (
            "It is worth noting that Hanoi is a blend of tradition and modernity. "
            "It is important to remember that visitors must be mindful of customs. "
            "The old town serves as a testament to the country's rich history, "
            "standing as a beacon of Vietnamese resilience. "
            "Let us delve deeper into this vibrant tapestry of street food."
        )
        report = linter.analyze_text(meta_slop)
        self.assertIn('tier6_violations', report, "Report must include tier6_violations")
        self.assertGreaterEqual(len(report['tier6_violations']), 3, "Must detect Tier 6 meta-commentary and empty signifiers")
        self.assertFalse(report['passed'], "Tier 6 meta-commentary must fail strict quality gate")

    def test_repetitive_adjective_clustering(self):
        clustered_text = (
            "The stunning bay offers breathtaking views from every angle. "
            "This stunning landmark is a truly breathtaking sight for travelers seeking unique experiences. "
            "The unique karst formations create a breathtaking panorama that remains stunning at dusk."
        )
        report = linter.analyze_text(clustered_text)
        self.assertIn('adjective_cluster_violations', report, "Report must include adjective_cluster_violations")
        self.assertGreaterEqual(len(report['adjective_cluster_violations']), 1, "Must detect excessive clustering of hyperbolic adjectives")
    def test_tier7_false_authority_and_conclusion_padding(self):
        text = (
            "It is no secret that Vietnam offers great street food. "
            "As any seasoned traveler knows, pho is best enjoyed on a low plastic stool. "
            "Needless to say, the broth takes ten hours to simmer. "
            "In conclusion, make no mistake about visiting Hanoi."
        )
        report = linter.analyze_text(text, source_name="test-tier7")
        self.assertIn('tier7_violations', report, "Report must include tier7_violations")
        self.assertGreaterEqual(report.get('tier7_count', 0), 3, "Must detect Tier 7 false authority and conclusion padding")
        self.assertFalse(report['passed'], "Tier 7 authority slop must fail strict quality gate")

    def test_local_cadence_monotony_detection(self):
        # 6 consecutive sentences with identical word count (12 words each) in narrative prose
        text = (
            "The ancient temple stands quietly beside the shimmering water of the wide lake. "
            "Local fishermen cast their nylon nets across the calm surface of the bay. "
            "Morning sunlight filters gently through the dense green canopy of coastal pine trees. "
            "Small wooden sampans drift lazily along the winding river toward the distant sea. "
            "Distant limestone mountains rise sharply into the misty horizon of the peaceful morning. "
            "Quiet village paths meander peacefully between the fertile green terraces of young rice."
        )
        report = linter.analyze_text(text, source_name="test-monotony")
        self.assertIn('local_cadence_violations', report, "Report must include local_cadence_violations")
        self.assertGreaterEqual(len(report.get('local_cadence_violations', [])), 1, "Must detect local cadence monotony across consecutive sentences")

    def test_passive_voice_density_threshold(self):
        # High passive density (all 4 sentences contain passive AI observer padding)
        text = (
            "Visitors are treated to beautiful sunset views from the terrace. "
            "It is recommended that travelers book their seats early. "
            "One can easily explore the grottoes by bicycle. "
            "It should be noted that the ticket office closes at five."
        )
        report = linter.analyze_text(text, source_name="test-passive-density")
        self.assertIn('passive_ratio', report, "Report must include passive_ratio")
        self.assertGreater(report.get('passive_ratio', 0.0), 0.15, "Must compute passive voice density ratio")

    def test_tier8_superficial_rhetoric_and_hollow_formulas(self):
        text = (
            "Vietnam is a rich tapestry of vibrant cultures and ancient customs. "
            "It is not just about visiting pagodas, it is about connecting with heritage. "
            "From vibrant street food stalls to secluded mountain valleys, Vietnam has it all. "
            "Nestled in the heart of the capital lies an oasis of calm. "
            "Have you ever wondered what makes street pho so special? "
            "Be prepared to enjoy mouth-watering cuisine in every corner."
        )
        report = linter.analyze_text(text, source_name="test-tier8")
        self.assertIn('tier8_violations', report, "Report must include tier8_violations")
        self.assertGreaterEqual(report.get('tier8_count', 0), 4, "Must detect Tier 8 superficial rhetoric and hollow formulas")
        self.assertFalse(report['passed'], "Tier 8 superficial rhetoric must fail strict quality gate")

    def test_lexical_diversity_analysis(self):
        # Extremely repetitive text with low vocabulary diversity
        text = (
            "The city is good. The city is nice. The city is big. "
            "The city is old. The city is calm. The city is quiet. "
            "The city is great. The city is cool. The city is fine."
        )
        report = linter.analyze_text(text, source_name="test-ttr")
        self.assertIn('lexical_diversity', report, "Report must include lexical_diversity")
        self.assertLess(report.get('lexical_diversity', 1.0), 0.50, "Repetitive text must have low lexical diversity")

    def test_clean_text_passes_tier8_and_lexical_diversity(self):
        text = (
            "Ga Hanoi serves daily southbound Reunification Express trains departing every evening. "
            "Passengers buy soft-berth four-person compartment tickets at counter five. "
            "A standard second-class ticket costs 1,150,000 VND to Ga Hue. "
            "Bring water and light snacks because station trolley carts offer limited options."
        )
        report = linter.analyze_text(text, source_name="test-clean-v8")
        self.assertEqual(report.get('tier8_count', 0), 0)
        self.assertGreater(report.get('lexical_diversity', 0.0), 0.65)
        self.assertTrue(report['passed'])

    def test_tier9_synthetic_contrast_and_sycophancy(self):
        text = (
            "The question is not whether Ha Long is scenic. The question is whether you should cruise overnight. "
            "No trip to Vietnam is complete without tasting street pho in Hanoi. "
            "Travelers will be hard-pressed to find a more authentic harbor. "
            "As dusk falls over the river, Hoi An bids farewell to the day. "
            "Fear not, we have got you covered with this guide. "
            "The view from the top is nothing short of spectacular."
        )
        report = linter.analyze_text(text, source_name="test-tier9")
        self.assertIn('tier9_violations', report, "Report must include tier9_violations")
        self.assertGreaterEqual(report.get('tier9_count', 0), 4, "Must detect Tier 9 synthetic contrast and sycophancy")
        self.assertFalse(report['passed'], "Tier 9 synthetic tropes must fail strict quality gate")

    def test_consecutive_identical_bigram_openers(self):
        text = (
            "Use a group tour when the schedule is simple and fixed timing is acceptable. "
            "Use a private driver when comfort, family pacing, and route flexibility matter more. "
            "Book tickets at the station counter three days ahead of travel date."
        )
        report = linter.analyze_text(text, source_name="test-bigram-openers")
        self.assertIn('repetitive_bigram_violations', report, "Report must include repetitive_bigram_violations")
        self.assertGreaterEqual(report.get('repetitive_bigram_count', 0), 1)
        self.assertIn('use a', [v['bigram'] for v in report.get('repetitive_bigram_violations', [])])

    def test_recalibrated_prose_flesch_reading_ease(self):
        # A page with large table markup where narrative prose sentences are concise
        html = (
            "<p>Hanoi railway station operates daily trains south along the coast. "
            "Passengers reserve four-berth soft sleeper tickets at counter five. "
            "Tickets cost 1,150,000 VND per person to Hue city.</p>"
            "<table><tr><td>Departure 19:30</td><td>Arrival 08:45</td></tr>"
            "<tr><td>Train SE3</td><td>Air-conditioned soft berth</td></tr>"
            "<tr><td>Luggage limit 20kg</td><td>Pillar 4 pickup</td></tr></table>"
        )
        report = linter.analyze_text(html, source_name="test-prose-flesch")
        self.assertGreater(report.get('flesch_reading_ease', 0.0), 30.0)
        self.assertLess(report.get('flesch_reading_ease', 100.0), 85.0)
        self.assertTrue(report['passed'])


if __name__ == '__main__':
    unittest.main()

