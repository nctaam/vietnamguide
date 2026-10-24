# -*- coding: utf-8 -*-
"""
Unit tests for the Ground-Truth Tariff & Freshness Sentinel (ops/tariff_sentinel.py).
"""
import datetime
import json
import tempfile
import unittest
from pathlib import Path

from ops.tariff_sentinel import (
    audit_route_registry,
    audit_toolkit_anchors,
    run_sentinel,
)


class TariffSentinelTests(unittest.TestCase):
    def test_production_sentinel_passes_all_282_routes_and_toolkits(self):
        """Production route registry and interactive toolkits must pass all sentinel checks."""
        passed, report = run_sentinel()
        self.assertTrue(passed, f"Production sentinel failed: {report}")
        self.assertEqual(report['status'], 'PASS')
        reg = report['registry']
        tk = report['toolkits']
        self.assertEqual(reg['route_count'], 282)
        self.assertEqual(len(reg['stale_routes']), 0)
        self.assertEqual(len(reg['invalid_date_routes']), 0)
        self.assertEqual(len(reg['title_violations']), 0)
        self.assertEqual(len(reg['description_violations']), 0)
        self.assertGreaterEqual(reg['empirical_coverage_pct'], 98.0)
        self.assertEqual(len(tk['missing_files']), 0)
        self.assertEqual(len(tk['missing_anchors']), 0)

    def test_audit_route_registry_detects_stale_dates_and_serp_violations(self):
        """Sentinel must flag stale last_reviewed dates, titles > 60 chars, and descriptions outside 147-157 chars."""
        with tempfile.TemporaryDirectory() as tmpdir:
            reg_path = Path(tmpdir) / 'route_registry.json'
            payload = {
                'routes': [
                    {
                        'path': 'destinations/stale-route',
                        'title': 'A' * 65,
                        'description': 'Short description without empirical signals.',
                        'last_reviewed': '2024-01-01',
                    }
                ]
            }
            reg_path.write_text(json.dumps(payload), encoding='utf-8')
            res = audit_route_registry(
                registry_path=reg_path,
                max_age_days=365,
                reference_date=datetime.date(2026, 9, 28),
            )
            self.assertEqual(res['route_count'], 1)
            self.assertEqual(len(res['stale_routes']), 1)
            self.assertEqual(len(res['title_violations']), 1)
            self.assertEqual(len(res['description_violations']), 1)
            self.assertEqual(res['empirical_counts']['any_empirical_signal'], 0)

    def test_audit_toolkit_anchors_detects_missing_files(self):
        """Toolkit anchor auditor must detect missing toolkit files in a synthetic theme directory."""
        with tempfile.TemporaryDirectory() as tmpdir:
            res = audit_toolkit_anchors(theme_inc_dir=Path(tmpdir))
            self.assertGreater(len(res['missing_files']), 0)


if __name__ == '__main__':
    unittest.main()
