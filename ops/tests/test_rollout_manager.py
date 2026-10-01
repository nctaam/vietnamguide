import json
from pathlib import Path
import tempfile
import unittest

from ops.rollout_manager import (
    DEFAULT_MANIFEST_PATH,
    DEFAULT_REGISTRY_PATH,
    generate_php_config,
    get_rollout_status,
    load_manifest,
    load_registry,
    verify_batches,
)


class RolloutManagerTests(unittest.TestCase):
    def test_load_production_manifest_and_registry(self):
        manifest = load_manifest(DEFAULT_MANIFEST_PATH)
        self.assertEqual(manifest.get("schema_version"), 1)
        self.assertEqual(manifest["summary"]["registry_route_count"], 282)
        self.assertEqual(manifest["summary"]["pilot_route_count"], 87)
        self.assertEqual(manifest["summary"]["pending_route_count"], 195)

        registry = load_registry(DEFAULT_REGISTRY_PATH)
        self.assertEqual(registry.get("schema_version"), 1)
        self.assertEqual(registry["route_count"], 282)
        self.assertEqual(len(registry["routes"]), 282)

    def test_rollout_status_calculations(self):
        status = get_rollout_status(DEFAULT_MANIFEST_PATH, DEFAULT_REGISTRY_PATH)
        self.assertEqual(status["total_canonical_routes"], 282)
        self.assertEqual(status["pilot_routes"], 87)
        self.assertEqual(status["pending_routes"], 195)
        self.assertEqual(len(status["batches"]), 3)

        # Batch 1: 50 -> 137
        self.assertEqual(status["batches"][0]["count"], 50)
        self.assertEqual(status["batches"][0]["cumulative_active"], 137)
        self.assertEqual(status["batches"][0]["percentage"], 48.58)

        # Batch 2: 50 -> 187
        self.assertEqual(status["batches"][1]["count"], 50)
        self.assertEqual(status["batches"][1]["cumulative_active"], 187)
        self.assertEqual(status["batches"][1]["percentage"], 66.31)

        # Batch 3: 95 -> 282
        self.assertEqual(status["batches"][2]["count"], 95)
        self.assertEqual(status["batches"][2]["cumulative_active"], 282)
        self.assertEqual(status["batches"][2]["percentage"], 100.0)

    def test_verify_production_batches_against_registry(self):
        result = verify_batches(DEFAULT_MANIFEST_PATH, DEFAULT_REGISTRY_PATH)
        self.assertTrue(result["valid"])
        self.assertEqual(result["verified_route_count"], 195)
        self.assertEqual(result["expected_route_count"], 195)
        self.assertEqual(result["errors"], [])

    def test_generate_php_config_rollback(self):
        config = generate_php_config("rollback")
        self.assertIn("define('VG_GUIDE_REGISTRY_ROLLOUT', []);", config)

    def test_generate_php_config_all(self):
        config = generate_php_config("all")
        self.assertIn("define('VG_GUIDE_REGISTRY_ROLLOUT', true);", config)

    def test_generate_php_config_batch_1(self):
        config = generate_php_config("batch_1")
        self.assertIn("define('VG_GUIDE_REGISTRY_ROLLOUT', [", config)
        self.assertIn("'compare/con-dao-vs-phu-quoc',", config)
        # Should have 50 route lines
        route_lines = [line for line in config.splitlines() if line.strip().startswith("'")]
        self.assertEqual(len(route_lines), 50)

    def test_generate_php_config_batch_2(self):
        config = generate_php_config("batch_2")
        self.assertIn("define('VG_GUIDE_REGISTRY_ROLLOUT', [", config)
        # Should have 100 route lines (50 from batch 1 + 50 from batch 2)
        route_lines = [line for line in config.splitlines() if line.strip().startswith("'")]
        self.assertEqual(len(route_lines), 100)

    def test_generate_php_config_invalid_stage_raises(self):
        with self.assertRaises(ValueError):
            generate_php_config("invalid_stage_xyz")

    def test_batch_1_canary_coverage_and_disjointness(self):
        manifest = load_manifest(DEFAULT_MANIFEST_PATH)
        registry = load_registry(DEFAULT_REGISTRY_PATH)

        pilot_paths = {r['path'] for r in registry['routes'] if r.get('current_template') == 'guide'}
        b1_paths = set(manifest['batches'][0]['paths'])

        self.assertEqual(len(pilot_paths), 87)
        self.assertEqual(len(b1_paths), 50)
        self.assertEqual(pilot_paths.intersection(b1_paths), set(), "Batch 1 must not overlap with pilot routes")

        combined = pilot_paths.union(b1_paths)
        self.assertEqual(len(combined), 137, "Pilot + Batch 1 must equal 137 routes")


if __name__ == "__main__":
    unittest.main()

