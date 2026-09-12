# -*- coding: utf-8 -*-
import unittest
import sys
import os

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..')))
from ops.anti_ai_slop_linter import analyze_text


class TestPolicyCadence(unittest.TestCase):
    def test_remediated_contact_cadence(self):
        contact_text = (
            "Reach the VietnamGuide editorial desk directly. "
            "We verify everything. "
            "Send route updates, tariff corrections, and bus timetable adjustments directly to editorial@vietnamguide.net. "
            "When travelers report an unexpected fare hike, road closure, or border checkpoint restriction, our ground team contacts transit dispatchers immediately to inspect the situation in person. "
            "Our field desk operates at 45 Le Duan Boulevard, Ben Nghe Ward, District 1, Ho Chi Minh City with liaison phone 028.3822.5555. "
            "We reply within 24 to 48 business hours. "
            "We do not accept sponsored content, paid link insertions, or hidden commercial compensation. "
            "Every recommendation stands on independent ground truth."
        )
        report = analyze_text(contact_text, source_name="contact")
        self.assertEqual(report['hls_score'], 100, f"HLS must be 100, got {report['hls_score']}")
        self.assertGreaterEqual(report['cv'], 0.45, f"CV must be >= 0.45, got {report['cv']}")
        self.assertTrue(report['passed'])

    def test_remediated_editorial_policy_cadence(self):
        policy_text = (
            "VietnamGuide publishes people-first travel guidance rooted in verifiable logistics. "
            "We do not publish generic itinerary filler. "
            "Our recommendations rest on a four-tier verification hierarchy that prioritizes primary government gazettes, transport operator timetables, and unannounced on-site audits. "
            "Commercial partnerships never dictate editorial rankings. "
            "When a rail timetable changes or an entrance tariff shifts, our editors verify the revision directly at dsvn.vn or provincial tourist portals. "
            "If an operator drops standards or inflates rates unfairly, we remove them from our recommendations immediately."
        )
        report = analyze_text(policy_text, source_name="editorial-policy")
        self.assertEqual(report['hls_score'], 100, f"HLS must be 100, got {report['hls_score']}")
        self.assertGreaterEqual(report['cv'], 0.45, f"CV must be >= 0.45, got {report['cv']}")
        self.assertTrue(report['passed'])

    def test_remediated_source_update_policy_cadence(self):
        source_text = (
            "Logistics shift. Timetables change. "
            "When travelers navigate cross-country transit between Hanoi, Da Nang, and Ho Chi Minh City, outdated railway schedules or unexpected ferry cancellations cause severe disruption, so VietnamGuide maintains structured ground audits across every single route. "
            "We review government immigration decrees, official entry portal fees, and 45-day visa exemption rules every 30 days. "
            "Train timetables receive quarterly audits. "
            "Every ninety days, our researchers cross-check Vietnam Railways schedules on dsvn.vn, expressway toll rates along CT01, and island ferry tariffs across Ha Long Bay and Phu Quoc against verified station booking counters. "
            "Weather updates occur twice yearly. "
            "When typhoon seasons approach the Central Coast, we adjust coastal warnings. "
            "Typo edits never change visible update dates. "
            "A revised timestamp signifies genuine ground-truth verification."
        )
        report = analyze_text(source_text, source_name="source-update-policy")
        self.assertEqual(report['hls_score'], 100, f"HLS must be 100, got {report['hls_score']}")
        self.assertGreaterEqual(report['cv'], 0.45, f"CV must be >= 0.45, got {report['cv']}")
    def test_remediated_privacy_policy_cadence(self):
        privacy_text = (
            "VietnamGuide.net operates an independent travel planning desk for international travelers. "
            "We safeguard reader data. "
            "We do not require user accounts or reader registrations. "
            "When you browse our travel itineraries, web servers automatically capture standard technical access logs, including your masked IP address, browser user-agent, operating system, requested URL path, referring domain, and timestamp. "
            "If you choose to contact our editorial desk directly via email regarding route updates, hotel closures, or transport corrections, we collect your email address and message contents to investigate your report. "
            "We use zero third-party tracking cookies for targeted advertising. "
            "Essential session cookies operate solely to support edge caching, rate limiting, and administrative security on our LiteSpeed web server cluster. "
            "Aggregate website traffic analysis runs with anonymized IP addresses to observe popular destination guides and detect broken transport links without tracking individual identity across the web. "
            "Our travel guides link directly to external logistics providers, official provincial tourism portals, and public transit schedules such as Vietnam Railways at dsvn.vn. "
            "When you click an external link, you navigate to an independent third-party domain governed by its own data privacy terms. "
            "We do not sell user data. "
            "Commercial partnerships never dictate route rankings. "
            "Technical web server access logs are retained for 30 days to diagnose network faults and block automated cyber attacks, after which log files are permanently deleted. "
            "Inquiries sent to our editorial desk are retained for 180 days to resolve ongoing transit investigations. "
            "All communication transmits across encrypted TLS 1.3 connections in accordance with Vietnam Personal Data Protection Decree 13/2023/ND-CP. "
            "You maintain full authority to review, rectify, or request deletion of any email correspondence submitted to our desk. "
            "For privacy inquiries or data removal requests, reach our compliance team at privacy@vietnamguide.net or contact our physical editorial liaison at 45 Le Duan Boulevard, Ben Nghe Ward, District 1, Ho Chi Minh City. "
            "We respond within 48 business hours."
        )
        report = analyze_text(privacy_text, source_name="privacy-policy")
        self.assertEqual(report['hls_score'], 100, f"HLS must be 100, got {report['hls_score']}")
        self.assertGreaterEqual(report['cv'], 0.45, f"CV must be >= 0.45, got {report['cv']}")
        self.assertTrue(report['passed'])

    def test_remediated_hub_benchmarks(self):
        hub_text = (
            "Distances in Vietnam deceive travelers. "
            "Plan intercity transfers using verified 2026 transit times, highway routes, and tariff baselines across main travel corridors. "
            "Shared limousines from Hanoi to Ha Long Bay take 2.5 hours via Expressway CT04 with fares from 250,000 to 300,000 VND. "
            "Hanoi to Ninh Binh takes only 1.5 hours. "
            "GrabCar transit between Da Nang and Hoi An Ancient Town averages 320,000 to 420,000 VND along the coastal road, whereas Train SE19 from Ga Hue to Ga Da Nang takes 2.5 hours via Hai Van Pass with soft seat tickets at 120,000 VND. "
            "Express vans to the Mekong Delta depart Western Bus Station for 140,000 to 180,000 VND."
        )
        report = analyze_text(hub_text, source_name="destinations-hub")
        self.assertEqual(report['hls_score'], 100, f"HLS must be 100, got {report['hls_score']}")
        self.assertGreaterEqual(report['edi'], 10.0, f"EDI must be >= 10.0, got {report['edi']}")
        self.assertTrue(report['passed'])


if __name__ == '__main__':
    unittest.main()
