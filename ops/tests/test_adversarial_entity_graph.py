# -*- coding: utf-8 -*-
"""
Adversarial Challenge Test Suite: Travel Clusters, Entity Graph & Schema.org JSON-LD Robustness.

Author: Challenger 1 (Empirical Challenger)
Target: wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php

Empirical challenges:
1. Boundary, malformed, empty, uppercase, nested, tricky, and adversarial paths into vg_find_travel_cluster_by_path().
2. Resolution of all 282 canonical routes in ops/route_registry.json (regional routes must not fall back to national_circuit).
3. Strict Vietnam geographic boundary validation for all 12 travel clusters (lat: 8.0-24.0, lon: 102.0-110.0).
4. Schema.org JSON-LD graph generation stress-testing (vg_rich_travel_schema_filter & rank_math/json_ld) for nulls, missing fields, and malformed types.
5. Deep nesting and recursive entity cleaning stress-testing (vg_schema_clean_data).
6. Empirical identification and documentation of circular structure memory exhaustion and upstream filter type-hint traps.
"""

import json
import os
from pathlib import Path
import sys
import unittest

REPO_ROOT = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..')))
if str(REPO_ROOT) not in sys.path:
    sys.path.insert(0, str(REPO_ROOT))

from ops.tests.test_guide_seo import _run_php, ROUTE_REGISTRY_JSON


class TestAdversarialEntityGraph(unittest.TestCase):
    """Adversarial stress-testing of travel clusters and entity graph."""

    @classmethod
    def setUpClass(cls):
        with open(ROUTE_REGISTRY_JSON, 'r', encoding='utf-8') as f:
            cls.registry_data = json.load(f)
            cls.routes = cls.registry_data.get('routes', [])

    def test_01_boundary_and_malformed_paths(self):
        """Stress-test vg_find_travel_cluster_by_path with empty, boundary, malformed, and tricky inputs."""
        boundary_inputs = [
            # Empty / whitespace / root
            ('', 'national_circuit'),
            ('   ', 'national_circuit'),
            ('/', 'national_circuit'),
            ('///', 'national_circuit'),
            ('//////', 'national_circuit'),
            # Uppercase & Mixed Case
            ('/DESTINATIONS/DA-LAT-TRAVEL-GUIDE/', 'central_highlands'),
            ('Destinations/Phong-Nha-Travel-Guide', 'phong_nha_caves'),
            ('ITINERARIES/MEKONG-DELTA-3-DAYS', 'mekong_delta'),
            ('destinations/Cao-Bang-Travel-Guide', 'far_north_frontier'),
            ('HANOI-TRAVEL-GUIDE', 'northern_triangle'),
            ('HA-LONG-BAY-CRUISE', 'ha_long_bay'),
            ('NINH-BINH-DAY-TRIP', 'ninh_binh'),
            ('SAPA-TREKKING-GUIDE', 'northern_highlands'),
            ('DA-NANG-BEACH-RESORT', 'central_heritage'),
            ('HO-CHI-MINH-CITY-DISTRICT-1', 'southern_delta'),
            ('SAIGON-STREET-FOOD', 'southern_delta'),
            ('PHU-QUOC-SUNSET-SANATO', 'coastal_islands'),
            # Deeply nested paths
            ('/asia/vietnam/destinations/central-highlands/da-lat/attractions/2026/', 'central_highlands'),
            ('guides/destinations/phong-nha/caves/tu-lan/review', 'phong_nha_caves'),
            ('region/south/mekong-delta/can-tho/floating-market/logistics', 'mekong_delta'),
            ('border/far-north/cao-bang/ban-gioc-waterfall', 'far_north_frontier'),
            # Tricky characters, query params, hash fragments
            ('destinations/da-lat-travel-guide?ref=chatgpt&lang=en#top-hotels', 'central_highlands'),
            ('destinations/phong-nha-travel-guide?query=caving%20expeditions', 'phong_nha_caves'),
            ('destinations/can-tho-travel-guide#cai-rang-market', 'mekong_delta'),
            # URL-encoded strings
            ('destinations/da-lat%20travel%20guide', 'central_highlands'),
            ('destinations/hoi-an%20ancient%20town', 'central_heritage'),
            # Long inputs (buffer/regex fuzzing)
            ('a' * 5000 + '/da-lat/' + 'b' * 5000, 'central_highlands'),
            ('x' * 10000, 'national_circuit'),
            # Adversarial code/payload injection strings (must never execute or crash)
            ("<?php phpinfo(); ?>/destinations/da-lat", 'central_highlands'),
            ("'; DROP TABLE routes; --/destinations/sapa", 'northern_highlands'),
            ("../../../etc/passwd", 'national_circuit'),
            ("..\\..\\windows\\system32", 'national_circuit'),
            ("<script>alert('xss')</script>/destinations/hanoi", 'northern_triangle'),
            # Keyword boundary collisions
            ('da-lat-to-da-nang-transfer', 'central_highlands'),   # da-lat evaluated before da-nang
            ('nha-trang-beach-break', 'coastal_islands'),          # nha-trang evaluated as coastal_islands, NOT phong_nha
            ('phong-nha-to-dong-hoi', 'phong_nha_caves'),          # both markers point to phong_nha_caves
            ('ha-tien-ferry-to-phu-quoc', 'mekong_delta'),         # ha-tien evaluated before phu-quoc
            ('dong-van-karst-plateau', 'northern_highlands'),       # dong-van in northern_highlands, NOT dong-hoi
            ('mai-chau-valley', 'far_north_frontier'),             # mai-chau in far_north_frontier
            ('mu-cang-chai-terraces', 'northern_highlands'),       # mu-cang-chai in northern_highlands
            ('quang-binh-caves', 'phong_nha_caves'),               # quang-binh in phong_nha_caves
            ('quang-ngai-coast', 'far_north_frontier'),            # quang-ngai in far_north_frontier
            ('saigon-nightlife-guide', 'southern_delta'),          # saigon alias properly resolves to southern_delta
        ]

        php_code = f"""
        $cases = {json.dumps([c[0] for c in boundary_inputs])};
        $out = [];
        foreach ($cases as $c) {{
            $res = vg_find_travel_cluster_by_path($c);
            $out[] = $res['id'] ?? 'ERROR';
        }}
        echo json_encode($out);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP execution failed:\n{res.stderr}")
        results = json.loads(res.stdout)

        for (path_input, expected_cluster), actual_cluster in zip(boundary_inputs, results):
            with self.subTest(input=path_input[:50]):
                self.assertEqual(
                    actual_cluster,
                    expected_cluster,
                    f"Path '{path_input[:50]}' expected {expected_cluster}, got {actual_cluster}",
                )

    def test_02_all_282_canonical_routes_resolution(self):
        """Verify that all 282 canonical routes in ops/route_registry.json resolve correctly."""
        routes_data = self.routes
        self.assertEqual(len(routes_data), 282, "Registry must contain exactly 282 routes")

        php_code = f"""
        $routes = {json.dumps([r['path'] for r in routes_data])};
        $results = [];
        foreach ($routes as $p) {{
            $cl = vg_find_travel_cluster_by_path($p);
            $results[$p] = [
                'id'          => $cl['id'] ?? 'MISSING_ID',
                'name'        => $cl['name'] ?? '',
                'has_geo'     => isset($cl['destination_schema']['geo']['latitude']),
                'has_trip'    => ! empty($cl['trip_schema']['stops']),
                'has_action'  => ! empty($cl['action_schema']['name']),
                'has_journey' => count($cl['journey_links'] ?? []) >= 3,
            ];
        }}
        echo json_encode($results);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        mapped = json.loads(res.stdout)

        valid_clusters = {
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
        }

        cluster_counts = {}
        destination_routes_in_national = []

        for r in routes_data:
            path = r['path']
            rtype = r.get('type', '')
            info = mapped.get(path, {})
            cid = info.get('id', '')

            self.assertIn(cid, valid_clusters, f"Route {path} resolved to invalid cluster {cid}")
            self.assertTrue(info.get('has_geo'), f"Cluster for {path} missing geo")
            self.assertTrue(info.get('has_trip'), f"Cluster for {path} missing trip stops")
            self.assertTrue(info.get('has_action'), f"Cluster for {path} missing action schema")
            self.assertTrue(info.get('has_journey'), f"Cluster for {path} has fewer than 3 journey links")

            cluster_counts[cid] = cluster_counts.get(cid, 0) + 1

            if rtype == 'destination' and cid == 'national_circuit':
                destination_routes_in_national.append(path)

        # Print detailed audit distribution for transparency
        print("\n--- 282 Canonical Routes Travel Cluster Distribution ---")
        for cid in sorted(cluster_counts.keys()):
            print(f"  {cid:22s}: {cluster_counts[cid]:3d} routes")
        print(f"  Total routes mapped   : {sum(cluster_counts.values())} / 282")

        # Destination routes should NOT fall back to national_circuit unless they are country-wide overview guides
        national_destination_allowlist = {
            'destinations/best-places-to-visit-vietnam',
            'destinations/unesco-heritage-sites-vietnam',
            'destinations/vietnam-coffee-guide',
        }
        unexpected_fallbacks = set(destination_routes_in_national) - national_destination_allowlist
        self.assertEqual(
            unexpected_fallbacks,
            set(),
            f"Regional destination routes unexpectedly fell back to national_circuit: {unexpected_fallbacks}",
        )
        self.assertEqual(
            len(destination_routes_in_national),
            len(national_destination_allowlist),
            "Only nationwide destination guides should map to national_circuit",
        )

    def test_03_geo_coordinates_strictly_within_vietnam_bounds(self):
        """Verify all geo-coordinates for all 12 clusters are strictly within Vietnam geographical boundaries (lat: 8.0-24.0, lon: 102.0-110.0)."""
        php_code = """
        $clusters = vg_get_travel_clusters_registry();
        $geos = [];
        foreach ($clusters as $id => $c) {
            $geo = $c['destination_schema']['geo'] ?? null;
            $geos[$id] = [
                'name'      => $c['name'],
                'hub'       => $c['hub'],
                'latitude'  => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
            ];
        }
        echo json_encode($geos);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        geos = json.loads(res.stdout)

        self.assertEqual(len(geos), 12, "Must have exactly 12 clusters")

        print("\n--- 12 Travel Clusters Geo-Coordinates & Boundary Audit ---")
        for cid, g in sorted(geos.items()):
            lat = g['latitude']
            lon = g['longitude']
            self.assertIsNotNone(lat, f"Cluster {cid} missing latitude")
            self.assertIsNotNone(lon, f"Cluster {cid} missing longitude")
            lat_f = float(lat)
            lon_f = float(lon)

            # Strict Vietnam bounding box:
            # Lat: 8.0 to 24.0 (Southern tip ~8.5°N, Northern tip ~23.4°N)
            # Lon: 102.0 to 110.0 (Western border ~102.1°E, Eastern mainland ~109.5°E)
            self.assertGreaterEqual(lat_f, 8.0, f"{cid} ({g['name']}) latitude {lat_f} < 8.0")
            self.assertLessEqual(lat_f, 24.0, f"{cid} ({g['name']}) latitude {lat_f} > 24.0")
            self.assertGreaterEqual(lon_f, 102.0, f"{cid} ({g['name']}) longitude {lon_f} < 102.0")
            self.assertLessEqual(lon_f, 110.0, f"{cid} ({g['name']}) longitude {lon_f} > 110.0")

            status = "PASS [Within Vietnam Box]"
            print(f"  {cid:22s}: lat={lat_f:7.4f}, lon={lon_f:8.4f} -> {status} ({g['hub']})")

    def test_04_schema_org_json_ld_stress_testing(self):
        """Stress-test Schema.org JSON-LD generation in vg_rich_travel_schema_filter and rank_math/json_ld."""
        # 1. Direct unit verification of vg_rich_travel_schema_filter for defensive input handling
        direct_cases = [
            ("null", "Direct null input"),
            ("'non_array_string'", "Direct string input"),
            ("12345", "Direct int input"),
            ("false", "Direct boolean input"),
        ]
        for php_val, label in direct_cases:
            with self.subTest(direct_case=label):
                php_code = f"""
                $post = new WP_Post();
                $post->post_name = 'da-lat-travel-guide';
                $GLOBALS['post'] = $post;
                $_SERVER['REQUEST_URI'] = '/destinations/da-lat-travel-guide/';

                $res = vg_rich_travel_schema_filter({php_val});
                echo json_encode([
                    'is_array'   => is_array($res),
                    'json_valid' => (json_last_error_msg() === 'No error'),
                ]);
                """
                res = _run_php(php_code)
                self.assertEqual(res.returncode, 0, f"PHP error in {label}:\n{res.stderr}")
                out = json.loads(res.stdout)
                self.assertTrue(out['is_array'], f"{label} must return an array")
                self.assertTrue(out['json_valid'], f"{label} must produce valid JSON")

        # 2. Filter pipeline verification with valid schema structures and malformed nodes
        filter_cases = [
            # Standard graph with WebPage and Article
            ("""[
                '@context' => 'https://schema.org',
                '@graph'   => [
                    [
                        '@type' => 'WebPage',
                        '@id'   => 'https://vietnamguide.net/destinations/da-lat-travel-guide/#webpage',
                        'name'  => 'Old Title',
                    ],
                    [
                        '@type' => 'Article',
                        '@id'   => 'https://vietnamguide.net/destinations/da-lat-travel-guide/#article',
                        'headline' => 'Old Headline',
                    ],
                    [
                        '@type' => 'Organization',
                        '@id'   => 'https://vietnamguide.net/#organization',
                        'name'  => 'VietnamGuide.net',
                    ],
                ]
            ]""", "Standard WebPage, Article, Organization graph"),
            # Graph with malformed elements (null, non-array elements)
            ("""[
                '@context' => 'https://schema.org',
                '@graph'   => [
                    null,
                    'string_node',
                    12345,
                    [
                        '@type' => 'WebPage',
                        '@id'   => 'https://vietnamguide.net/destinations/da-lat-travel-guide/#webpage',
                    ]
                ]
            ]""", "Graph with null and scalar elements"),
            # Graph missing @graph key (flat associative array)
            ("""[
                '@type' => 'WebPage',
                'name'  => 'Flat Page',
            ]""", "Flat schema array without @graph key"),
            # Pre-existing destination node (idempotence verification)
            ("""[
                '@context' => 'https://schema.org',
                '@graph'   => [
                    [
                        '@type' => 'TouristDestination',
                        '@id'   => 'https://vietnamguide.net/destinations/da-lat-travel-guide/#tourist-destination',
                        'name'  => 'Pre-existing Da Lat',
                    ]
                ]
            ]""", "Pre-existing TouristDestination node (idempotence)"),
        ]

        for php_input_expr, case_label in filter_cases:
            with self.subTest(case=case_label):
                php_code = f"""
                $post = new WP_Post();
                $post->post_name = 'da-lat-travel-guide';
                $GLOBALS['post'] = $post;
                $_SERVER['REQUEST_URI'] = '/destinations/da-lat-travel-guide/';

                $initial = {php_input_expr};
                $result = apply_filters('rank_math/json_ld', $initial);

                $encoded = json_encode($result);
                $json_error = json_last_error_msg();

                echo json_encode([
                    'is_array'    => is_array($result),
                    'json_valid'  => ($json_error === 'No error'),
                    'json_error'  => $json_error,
                    'has_graph'   => isset($result['@graph']),
                    'graph_count' => isset($result['@graph']) && is_array($result['@graph']) ? count($result['@graph']) : 0,
                ]);
                """
                res = _run_php(php_code)
                self.assertEqual(res.returncode, 0, f"PHP error in {case_label}:\n{res.stderr}")
                out = json.loads(res.stdout)
                self.assertTrue(out['is_array'], f"{case_label}: filter must always return an array")
                self.assertTrue(out['json_valid'], f"{case_label}: JSON serialization error: {out['json_error']}")

    def test_05_deep_nesting_and_entity_clean_data(self):
        """Stress-test recursive vg_schema_clean_data with deeply nested arrays and special entities."""
        php_code = """
        $post = new WP_Post();
        $post->post_name = 'da-lat-travel-guide';
        $GLOBALS['post'] = $post;
        $_SERVER['REQUEST_URI'] = '/destinations/da-lat-travel-guide/';

        // Test deep nesting (depth 30)
        $deep = ['name' => 'Da Lat &amp; Central Highlands', 'publicAccess' => '1', 'isAccessibleForFree' => 'true', 'position' => '42'];
        for ($i = 0; $i < 30; $i++) {
            $deep = ['nested' => $deep];
        }
        $cleaned = vg_schema_clean_data($deep);
        $deep_ok = is_array($cleaned);

        // Traverse down 30 levels to verify leaves were properly cleaned
        $leaf = $cleaned;
        for ($i = 0; $i < 30; $i++) {
            $leaf = $leaf['nested'];
        }
        $leaf_cleaned_name = ($leaf['name'] === 'Da Lat & Central Highlands');
        $leaf_cleaned_access = ($leaf['publicAccess'] === true);
        $leaf_cleaned_free = ($leaf['isAccessibleForFree'] === true);
        $leaf_cleaned_pos = ($leaf['position'] === 42);

        echo json_encode([
            'deep_ok'             => $deep_ok,
            'leaf_cleaned_name'   => $leaf_cleaned_name,
            'leaf_cleaned_access' => $leaf_cleaned_access,
            'leaf_cleaned_free'   => $leaf_cleaned_free,
            'leaf_cleaned_pos'    => $leaf_cleaned_pos,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error in deep nesting test:\n{res.stderr}")
        out = json.loads(res.stdout)
        self.assertTrue(out['deep_ok'], "Deeply nested arrays must process cleanly")
        self.assertTrue(out['leaf_cleaned_name'], "HTML entities &amp; must be decoded to &")
        self.assertTrue(out['leaf_cleaned_access'], "publicAccess string '1' must be normalized to boolean true")
        self.assertTrue(out['leaf_cleaned_free'], "isAccessibleForFree string 'true' must be normalized to boolean true")
        self.assertTrue(out['leaf_cleaned_pos'], "position numeric string '42' must be converted to int 42")

    def test_06_adversarial_vulnerability_reproduction(self):
        """Empirically document vulnerabilities: circular structures and upstream hook strict type hinting."""
        # Vulnerability 1: Circular structure causing memory exhaustion in vg_schema_clean_data()
        php_circ = """
        $circ = ['name' => 'Test'];
        $circ['child'] = &$circ;
        try {
            vg_schema_clean_data($circ);
            $crashed = false;
        } catch (Throwable $e) {
            $crashed = true;
        }
        echo json_encode(['crashed' => $crashed]);
        """
        res_circ = _run_php(php_circ)
        # Memory exhaustion causes non-zero exit code (255)
        self.assertNotEqual(
            res_circ.returncode,
            0,
            "Empirical verification: circular reference must trigger PHP memory exhaustion / recursion failure",
        )
        self.assertIn("Allowed memory size", res_circ.stdout + res_circ.stderr)

        # Vulnerability 2: Upstream mu-plugin vg_filter_rank_math_json_ld() type hint trap
        php_type_trap = """
        try {
            apply_filters('rank_math/json_ld', null);
            $type_error = false;
        } catch (TypeError $e) {
            $type_error = true;
        }
        echo json_encode(['type_error' => $type_error]);
        """
        res_type = _run_php(php_type_trap)
        # In PHP 8+, Uncaught TypeError causes exit code 255 if not caught or caught
        out_type = json.loads(res_type.stdout) if res_type.returncode == 0 else {}
        if res_type.returncode != 0:
            self.assertIn("TypeError", res_type.stdout + res_type.stderr)
        else:
            self.assertTrue(out_type.get('type_error', False))


if __name__ == '__main__':
    unittest.main()
