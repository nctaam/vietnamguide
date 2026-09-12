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
        self.assertTrue(report['passed'])


if __name__ == '__main__':
    unittest.main()
