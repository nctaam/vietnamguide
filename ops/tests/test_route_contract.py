import os
import json
import shutil
import subprocess
import sys
import tempfile
import textwrap
import unittest
from pathlib import Path

REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
if REPO_ROOT not in sys.path:
    sys.path.insert(0, REPO_ROOT)

from ops.route_contract import (
    classify_path,
    load_registry,
    load_rollout_manifest,
    normalize_path,
    route_set_diff,
    validate_rollout_manifest,
    validate_registry,
)

REPO_PATH = Path(REPO_ROOT)


class RouteContractTests(unittest.TestCase):
    def _run_php_harness(self, body: str, theme_file_root: Path | None = None) -> subprocess.CompletedProcess[str]:
        """Run a small, dependency-free PHP harness against the theme functions."""
        include_root = (
            REPO_PATH
            / 'wordpress'
            / 'wp-content'
            / 'themes'
            / 'vietnamguide-premium'
        )
        theme_root = theme_file_root or (
            REPO_PATH
            / 'wordpress'
            / 'wp-content'
            / 'themes'
            / 'vietnamguide-premium'
        )
        php_string = json.dumps(str(theme_root).replace('\\', '/'))
        routing_path = json.dumps(
            str(include_root / 'inc' / 'guide-routing.php').replace('\\', '/')
        )
        aio_path = json.dumps(str(include_root / 'inc' / 'guide-aio.php').replace('\\', '/'))
        script = textwrap.dedent(
            f'''\
            <?php
            define('ABSPATH', '/wordpress/');
            class WP_Post {{
                public $ID;
                public $post_type;
                public $post_parent = 0;
                public $post_password = '';
                public $post_content = '';
                public $test_path = '';
            }}
            function get_theme_file_path($relative) {{
                $root = {php_string};
                return $root . '/' . ltrim(str_replace('\\\\', '/', $relative), '/');
            }}
            function add_action(...$args) {{}}
            function add_filter(...$args) {{}}
            function apply_filters($tag, $value, ...$args) {{ return $value; }}
            function get_post($id = null) {{ return $GLOBALS['vg_test_post'] ?? null; }}
            function is_page($id = null) {{ return isset($GLOBALS['vg_test_post']); }}
            function get_page_uri($post) {{ return $post->test_path ?? ''; }}
            function home_url($path = '') {{ return 'https://vietnamguide.net' . $path; }}
            function untrailingslashit($value) {{ return rtrim($value, '/'); }}
            require {routing_path};
            require {aio_path};
            {body}
            '''
        )
        with tempfile.NamedTemporaryFile('w', suffix='.php', encoding='utf-8', delete=False) as handle:
            handle.write(script)
            script_path = handle.name
        try:
            return subprocess.run(
                ['php', script_path],
                cwd=REPO_PATH,
                capture_output=True,
                text=True,
                check=False,
            )
        finally:
            Path(script_path).unlink(missing_ok=True)

    def test_normalize_path_removes_origin_query_fragment_and_trailing_slash(self):
        self.assertEqual(
            normalize_path('https://vietnamguide.net/plan/vietnam-evisa/?utm_source=x#faq'),
            'plan/vietnam-evisa',
        )

    def test_normalize_path_rejects_ambiguous_segments(self):
        with self.assertRaises(ValueError):
            normalize_path('/plan//vietnam-evisa/')
        with self.assertRaises(ValueError):
            normalize_path('/plan/../vietnam-evisa/')

    def test_classify_path_recognizes_supported_route_prefixes_only(self):
        self.assertEqual(classify_path('destinations/hanoi-travel-guide'), 'destination')
        self.assertEqual(classify_path('itineraries/10-days-in-vietnam'), 'itinerary')
        self.assertEqual(classify_path('compare/hanoi-vs-ho-chi-minh-city'), 'comparison')
        self.assertEqual(classify_path('plan/vietnam-evisa'), 'practical')
        self.assertIsNone(classify_path('privacy-policy'))
        self.assertIsNone(classify_path('destinations'))

    def test_validate_registry_rejects_duplicate_paths_and_missing_required_metadata(self):
        registry = [
            {
                'path': 'plan/vietnam-evisa',
                'type': 'practical',
                'title': 'Vietnam e-visa',
                'description': 'Visa guide',
                'status': 'published',
                'template': 'guide',
            },
            {
                'path': '/plan/vietnam-evisa/',
                'type': 'practical',
                'title': '',
                'description': 'Duplicate',
                'status': 'published',
                'template': 'guide',
            },
        ]

        errors = validate_registry(registry)

        self.assertIn('duplicate path: plan/vietnam-evisa', errors)
        self.assertIn('missing title: plan/vietnam-evisa', errors)

    def test_validate_registry_accepts_a_complete_unique_route(self):
        registry = [
            {
                'path': 'plan/vietnam-evisa',
                'type': 'practical',
                'title': 'Vietnam e-visa',
                'description': 'Visa guide',
                'status': 'published',
                'template': 'guide',
                'current_template': 'guide',
                'parent': 'plan',
                'last_reviewed': '2026-09-28',
                'source': 'test',
                'content_owner': 'test owner',
            }
        ]

        self.assertEqual(validate_registry(registry), [])

    def test_registry_artifact_matches_the_frozen_route_baseline(self):
        registry = load_registry(REPO_PATH / 'ops' / 'route_registry.json')
        baseline = json.loads(
            (REPO_PATH / 'docs' / 'baselines' / '2026-09-28-route-baseline.json').read_text(
                encoding='utf-8'
            )
        )
        expected = set(baseline['routes']['pilot']) | set(baseline['routes']['legacy'])
        actual = {record['path'] for record in registry}

        self.assertEqual(validate_registry(registry), [])
        self.assertEqual(len(registry), 282)
        self.assertEqual(route_set_diff(expected, actual), (set(), set()))

    def test_theme_registry_artifact_matches_ops_registry(self):
        ops_registry = load_registry(REPO_PATH / 'ops' / 'route_registry.json')
        theme_registry = load_registry(
            REPO_PATH
            / 'wordpress'
            / 'wp-content'
            / 'themes'
            / 'vietnamguide-premium'
            / 'inc'
            / 'guide-route-registry.json'
        )

        self.assertEqual(
            {record['path'] for record in theme_registry},
            {record['path'] for record in ops_registry},
        )
        self.assertEqual(
            {record['type'] for record in theme_registry},
            {record['type'] for record in ops_registry},
        )
        self.assertEqual(
            sum(record['current_template'] == 'guide' for record in theme_registry),
            87,
        )
        self.assertEqual(
            sum(record['migration_status'] == 'pending' for record in theme_registry),
            195,
        )

    def test_rollout_manifest_covers_every_pending_route_once(self):
        registry = load_registry(REPO_PATH / 'ops' / 'route_registry.json')
        manifest = load_rollout_manifest(
            REPO_PATH / 'docs' / 'baselines' / '2026-09-28-rollout-batches.json'
        )

        self.assertEqual(validate_rollout_manifest(registry, manifest), [])
        self.assertEqual(manifest['summary']['pending_route_count'], 195)
        self.assertEqual(
            [batch['count'] for batch in manifest['batches']],
            [50, 50, 95],
        )

    def test_rollout_manifest_rejects_duplicate_and_unknown_paths(self):
        registry = [
            {
                'path': 'plan/vietnam-evisa',
                'type': 'practical',
                'title': 'Vietnam e-visa',
                'description': 'Visa guide',
                'status': 'published',
                'template': 'guide',
                'current_template': 'legacy',
            }
        ]
        manifest = {
            'schema_version': 1,
            'batches': [
                {
                    'id': 'batch-1',
                    'count': 2,
                    'paths': ['plan/vietnam-evisa', 'unknown/path'],
                },
                {
                    'id': 'batch-2',
                    'count': 1,
                    'paths': ['plan/vietnam-evisa'],
                },
            ],
        }

        errors = validate_rollout_manifest(registry, manifest)

        self.assertIn('duplicate rollout path: plan/vietnam-evisa', errors)
        self.assertIn('unknown rollout path: unknown/path', errors)
        self.assertIn('rollout manifest path coverage mismatch', errors)

    def test_aio_registry_inventory_reads_all_published_routes(self):
        result = self._run_php_harness(
            """
            $routes = vg_aio_registry_inventory();
            echo count($routes), "\\n";
            echo $routes['plan/vietnam-evisa']['title'], "\\n";
            """
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(
            result.stdout.splitlines(),
            ['282', 'Vietnam E-Visa Guide: Official Portal, Fees and Mistakes'],
        )

    def test_aio_registry_inventory_path_set_matches_frozen_registry(self):
        result = self._run_php_harness(
            "echo implode(\"\\n\", array_keys(vg_aio_registry_inventory()));"
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        runtime_paths = {path for path in result.stdout.splitlines() if path}
        registry_paths = {
            record['path']
            for record in load_registry(REPO_PATH / 'ops' / 'route_registry.json')
            if record['status'] == 'published'
        }
        self.assertEqual(runtime_paths, registry_paths)

    def test_aio_inventory_falls_back_to_legacy_when_registry_is_unavailable(self):
        missing_root = Path(tempfile.mkdtemp(prefix='vietnamguide-missing-registry-'))
        try:
            result = self._run_php_harness(
                "echo count(vg_aio_routes_inventory()), \"\\n\";",
                theme_file_root=missing_root,
            )
        finally:
            missing_root.rmdir()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), '87')

    def test_aio_inventory_falls_back_when_registry_json_is_malformed(self):
        malformed_root = Path(tempfile.mkdtemp(prefix='vietnamguide-malformed-registry-'))
        (malformed_root / 'inc').mkdir()
        (malformed_root / 'inc' / 'guide-route-registry.json').write_text(
            '{ malformed json', encoding='utf-8'
        )
        try:
            result = self._run_php_harness(
                "echo count(vg_aio_routes_inventory()), \"\\n\";",
                theme_file_root=malformed_root,
            )
        finally:
            shutil.rmtree(malformed_root)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), '87')

    def test_aio_inventory_falls_back_when_registry_record_is_incomplete(self):
        incomplete_root = Path(tempfile.mkdtemp(prefix='vietnamguide-incomplete-registry-'))
        (incomplete_root / 'inc').mkdir()
        (incomplete_root / 'inc' / 'guide-route-registry.json').write_text(
            json.dumps(
                {
                    'routes': [
                        {
                            'path': 'plan/vietnam-evisa',
                            'type': 'practical',
                            'status': 'published',
                            'template': 'guide',
                            'current_template': 'guide',
                        }
                    ]
                }
            ),
            encoding='utf-8',
        )
        try:
            result = self._run_php_harness(
                "echo count(vg_aio_routes_inventory()), \"\\n\";",
                theme_file_root=incomplete_root,
            )
        finally:
            shutil.rmtree(incomplete_root)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), '87')

    def test_aio_inventory_falls_back_when_registry_is_truncated_but_records_are_valid(self):
        truncated_root = Path(tempfile.mkdtemp(prefix='vietnamguide-truncated-registry-'))
        (truncated_root / 'inc').mkdir()
        (truncated_root / 'inc' / 'guide-route-registry.json').write_text(
            json.dumps(
                {
                    'schema_version': 1,
                    'route_count': 1,
                    'routes': [
                        {
                            'path': 'plan/vietnam-evisa',
                            'type': 'practical',
                            'title': 'Vietnam E-Visa Guide',
                            'description': 'Visa guide',
                            'status': 'published',
                            'template': 'guide',
                            'current_template': 'guide',
                            'parent': 'plan',
                            'last_reviewed': '2026-09-28',
                            'source': 'test',
                            'content_owner': 'test owner',
                        }
                    ],
                }
            ),
            encoding='utf-8',
        )
        try:
            result = self._run_php_harness(
                "echo count(vg_aio_routes_inventory()), \"\\n\";",
                theme_file_root=truncated_root,
            )
        finally:
            shutil.rmtree(truncated_root)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), '87')

    def test_aio_inventory_falls_back_when_one_full_registry_record_is_invalid(self):
        invalid_root = Path(tempfile.mkdtemp(prefix='vietnamguide-invalid-full-registry-'))
        (invalid_root / 'inc').mkdir()
        payload = json.loads(
            (
                REPO_PATH
                / 'wordpress'
                / 'wp-content'
                / 'themes'
                / 'vietnamguide-premium'
                / 'inc'
                / 'guide-route-registry.json'
            ).read_text(encoding='utf-8')
        )
        payload['routes'][0]['title'] = ''
        (invalid_root / 'inc' / 'guide-route-registry.json').write_text(
            json.dumps(payload), encoding='utf-8'
        )
        try:
            result = self._run_php_harness(
                "echo count(vg_aio_routes_inventory()), \"\\n\";",
                theme_file_root=invalid_root,
            )
        finally:
            shutil.rmtree(invalid_root)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), '87')

    def test_aio_inventory_falls_back_when_registry_type_does_not_match_path(self):
        mismatch_root = Path(tempfile.mkdtemp(prefix='vietnamguide-mismatch-registry-'))
        (mismatch_root / 'inc').mkdir()
        (mismatch_root / 'inc' / 'guide-route-registry.json').write_text(
            json.dumps(
                {
                    'routes': [
                        {
                            'path': 'plan/vietnam-evisa',
                            'type': 'destination',
                            'title': 'Vietnam E-Visa Guide',
                            'description': 'Visa guide',
                            'status': 'published',
                            'template': 'guide',
                            'current_template': 'guide',
                        }
                    ]
                }
            ),
            encoding='utf-8',
        )
        try:
            result = self._run_php_harness(
                "echo count(vg_aio_routes_inventory()), \"\\n\";",
                theme_file_root=mismatch_root,
            )
        finally:
            shutil.rmtree(mismatch_root)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), '87')

    def test_aio_full_directory_heading_uses_registry_route_count(self):
        result = self._run_php_harness(
            """
            $_SERVER['REQUEST_URI'] = '/llms-full.txt';
            vg_handle_llms_txt_request();
            """
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('## 3. Comprehensive Directory of Curated Routes (282 Guides)', result.stdout)
        self.assertNotIn('(87 Guides)', result.stdout)
        self.assertEqual(result.stdout.count('Route Segment:'), 282)

    def test_registry_rollout_is_opt_in_and_can_select_one_legacy_route(self):
        result = self._run_php_harness(
            """
            define('VG_GUIDE_REGISTRY_ROLLOUT', ['compare/con-dao-vs-phu-quoc']);
            $post = new WP_Post();
            $post->ID = 1001;
            $post->post_type = 'page';
            $post->test_path = 'compare/con-dao-vs-phu-quoc';
            $GLOBALS['vg_test_post'] = $post;
            echo vg_is_guide_experience_page($post) ? "1\\n" : "0\\n";
            $post->test_path = 'compare/ly-son-vs-cham-islands';
            echo vg_is_guide_experience_page($post) ? "1\\n" : "0\\n";
            """
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.splitlines(), ['1', '0'])

    def test_registry_rollout_all_enables_published_target_routes(self):
        result = self._run_php_harness(
            """
            define('VG_GUIDE_REGISTRY_ROLLOUT', true);
            $post = new WP_Post();
            $post->ID = 1002;
            $post->post_type = 'page';
            $post->test_path = 'destinations/ba-be-lake-travel-guide';
            $GLOBALS['vg_test_post'] = $post;
            echo vg_is_guide_experience_page($post) ? "1\\n" : "0\\n";
            echo count(vg_guide_registry_rollout_paths()), "\\n";
            """
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.splitlines(), ['1', '282'])

    def test_rollout_batches_partition_pending_routes_with_zero_overlap(self):
        registry = load_registry(REPO_PATH / 'ops' / 'route_registry.json')
        manifest = load_rollout_manifest(
            REPO_PATH / 'docs' / 'baselines' / '2026-09-28-rollout-batches.json'
        )

        batches = manifest.get('batches', [])
        self.assertEqual(len(batches), 3)

        b1_paths = set(batches[0].get('paths', []))
        b2_paths = set(batches[1].get('paths', []))
        b3_paths = set(batches[2].get('paths', []))

        self.assertEqual(len(b1_paths), 50)
        self.assertEqual(len(b2_paths), 50)
        self.assertEqual(len(b3_paths), 95)
        self.assertEqual(len(b1_paths) + len(b2_paths) + len(b3_paths), 195)

        # Zero pairwise overlap
        self.assertEqual(b1_paths.intersection(b2_paths), set())
        self.assertEqual(b1_paths.intersection(b3_paths), set())
        self.assertEqual(b2_paths.intersection(b3_paths), set())

        # Exact partition of pending routes
        pending_routes = {
            record['path']
            for record in registry
            if record.get('migration_status') == 'pending'
        }
        self.assertEqual(len(pending_routes), 195)
        self.assertEqual(b1_paths | b2_paths | b3_paths, pending_routes)

        # Zero overlap with active pilot routes
        pilot_routes = {
            record['path']
            for record in registry
            if record.get('current_template') == 'guide'
        }
        self.assertEqual(len(pilot_routes), 87)
        self.assertEqual((b1_paths | b2_paths | b3_paths).intersection(pilot_routes), set())

    def test_public_verifier_executes_fixtures_only_cleanly(self):
        verifier_path = REPO_PATH / 'ops' / 'verify-guide-experience-public.ps1'
        self.assertTrue(verifier_path.is_file(), f"Verifier script not found: {verifier_path}")

        # The HTML COM parser fixture requires Windows PowerShell 5.1 (powershell.exe)
        if sys.platform != 'win32':
            self.skipTest("HTML COM parser fixtures require Windows PowerShell (powershell.exe)")

        ps_exe = shutil.which('powershell.exe') or shutil.which('powershell')
        if not ps_exe:
            self.skipTest("Windows PowerShell (powershell.exe) not found")

        result = subprocess.run(
            [ps_exe, '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', str(verifier_path), '-FixturesOnly'],
            capture_output=True,
            text=True,
            timeout=30,
        )
        self.assertEqual(result.returncode, 0, f"Public verifier fixtures failed: {result.stdout}\n{result.stderr}")
        self.assertIn('fixtures passed', result.stdout.lower())

    def test_public_verifier_dynamic_batch_route_counts_and_ast_invariants(self):
        verifier_path = REPO_PATH / 'ops' / 'verify-guide-experience-public.ps1'
        content = verifier_path.read_text(encoding='utf-8')

        # Check parameter declarations
        self.assertIn("ValidateSet('pilot', 'batch_1', 'batch_2', 'batch_3', 'all', '1', '2', '3')", content)
        self.assertIn("[string]$Batch = 'all'", content)
        self.assertIn('[switch]$AllRoutes', content)
        self.assertIn('[string]$RegistryPath = \'\'', content)
        self.assertIn('[string]$ManifestPath = \'\'', content)
        self.assertIn('[int]$MaxRoutes = 0', content)

        # Check AST invariants and critical search strings
        self.assertIn('function Get-PublicVerificationRoutes', content)
        self.assertIn('foreach ($PilotPath in $PilotPaths) {', content)
        self.assertIn('if ($Page.Dom.H1Count -ne 1) {', content)
        self.assertIn('if (-not $Page.Dom.HasGuideShell) {', content)
        self.assertIn('if (-not $Page.Dom.HasGuideNavigation) {', content)
        self.assertIn('$NonPilotPaths = @(', content)
        self.assertIn('$PilotPaths = @(', content)

        # Dynamic PowerShell verification requires Windows PowerShell (powershell.exe)
        if sys.platform != 'win32':
            return

        ps_exe = shutil.which('powershell.exe') or shutil.which('powershell')
        if not ps_exe:
            return

        # Check dynamic route resolution via PowerShell test harness
        ps_test = textwrap.dedent("""
            . ./ops/verify-guide-experience-public.ps1 -FixturesOnly | Out-Null
            $p = (Get-PublicVerificationRoutes -Batch 'pilot' -FallbackRoutes $PilotPaths).Count
            $b1 = (Get-PublicVerificationRoutes -Batch 'batch_1').Count
            $b2 = (Get-PublicVerificationRoutes -Batch 'batch_2').Count
            $b3 = (Get-PublicVerificationRoutes -Batch 'batch_3').Count
            $all = (Get-PublicVerificationRoutes -Batch 'all').Count
            $max5 = (Get-PublicVerificationRoutes -Batch 'all' -MaxRoutes 5).Count
            "$p,$b1,$b2,$b3,$all,$max5"
        """)
        result = subprocess.run(
            [ps_exe, '-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', ps_test],
            cwd=str(REPO_PATH),
            capture_output=True,
            text=True,
            timeout=30,
        )
        self.assertEqual(result.returncode, 0, f"PowerShell batch test failed: {result.stdout}\n{result.stderr}")
        counts = result.stdout.strip().split(',')
        self.assertEqual(counts, ['87', '50', '50', '95', '282', '5'])

    def test_routing_engine_o1_lookup_and_telemetry_contracts(self):
        result = self._run_php_harness(
            """
            $pilotPaths = vg_guide_pilot_paths();
            $pilotMap = vg_guide_pilot_paths_map();
            echo count($pilotPaths), "\\n";
            echo count($pilotMap), "\\n";
            echo isset($pilotMap['destinations/hanoi-travel-guide']) ? "1\\n" : "0\\n";
            echo isset($pilotMap['non-existent/path']) ? "1\\n" : "0\\n";

            $rolloutPaths = vg_guide_registry_rollout_paths();
            $rolloutMap = vg_guide_registry_rollout_paths_map();
            echo count($rolloutPaths), "\\n";
            echo count($rolloutMap), "\\n";
            echo isset($rolloutMap['destinations/hanoi-travel-guide']) ? "1\\n" : "0\\n";

            // Test fallback telemetry logging
            vg_record_route_fallback('legacy-path/sample', 'non_guide_page');
            $logs = vg_get_route_fallback_telemetry();
            echo count($logs), "\\n";
            echo $logs[0]['path'], "\\n";
            echo $logs[0]['reason'], "\\n";
            """
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(
            result.stdout.splitlines(),
            [
                '87',
                '87',
                '1',
                '0',
                '282',
                '282',
                '1',
                '1',
                'legacy-path/sample',
                'non_guide_page',
            ],
        )

    def test_routing_engine_flexible_schema_count_validation(self):
        flexible_root = Path(tempfile.mkdtemp(prefix='vietnamguide-flexible-registry-'))
        (flexible_root / 'inc').mkdir()
        payload = json.loads(
            (
                REPO_PATH
                / 'wordpress'
                / 'wp-content'
                / 'themes'
                / 'vietnamguide-premium'
                / 'inc'
                / 'guide-route-registry.json'
            ).read_text(encoding='utf-8')
        )
        # Add an additional valid 283rd route dynamically
        extra_route = dict(payload['routes'][0])
        extra_route['path'] = 'destinations/test-expanded-destination-guide'
        extra_route['type'] = 'destination'
        extra_route['parent'] = 'destinations'
        extra_route['title'] = 'Test Expanded Destination Guide'
        extra_route['description'] = 'Expanded travel route for stress testing registry scalability without breaking.'
        payload['routes'].append(extra_route)
        payload['route_count'] = len(payload['routes'])

        (flexible_root / 'inc' / 'guide-route-registry.json').write_text(
            json.dumps(payload), encoding='utf-8'
        )
        try:
            result = self._run_php_harness(
                """
                $registry = vg_guide_route_registry();
                echo count($registry), "\\n";
                echo isset($registry['destinations/test-expanded-destination-guide']) ? "1\\n" : "0\\n";
                """,
                theme_file_root=flexible_root,
            )
        finally:
            shutil.rmtree(flexible_root)
        self.assertEqual(result.returncode, 0, result.stderr)

    def test_canonical_alias_redirect_map_and_404_suppression(self):
        """Routing engine must define canonical aliases for destinations and suppress 404 guessing."""
        result = self._run_php_harness(
            """
            $map = vg_get_canonical_alias_redirect_map();
            echo count($map) >= 30 ? "1\\n" : "0\\n";
            echo isset($map['destinations/hanoi']) && $map['destinations/hanoi'] === '/destinations/hanoi-travel-guide/' ? "1\\n" : "0\\n";
            echo isset($map['destinations/da-nang']) && $map['destinations/da-nang'] === '/destinations/da-nang-travel-guide/' ? "1\\n" : "0\\n";
            echo isset($map['destinations/ha-long-bay']) && $map['destinations/ha-long-bay'] === '/destinations/ha-long-bay-travel-guide/' ? "1\\n" : "0\\n";
            echo isset($map['destinations/sapa']) && $map['destinations/sapa'] === '/destinations/sapa-travel-guide/' ? "1\\n" : "0\\n";
            echo isset($map['destinations/ho-chi-minh-city']) && $map['destinations/ho-chi-minh-city'] === '/destinations/ho-chi-minh-city-travel-guide/' ? "1\\n" : "0\\n";
            """
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.splitlines(), ['1', '1', '1', '1', '1', '1'])

    def test_split_guide_blocks_handles_nested_divs_and_sections_in_legacy_hero(self):
        """vg_split_guide_blocks must accurately track nested tag depth in legacy hero blocks and fail closed on malformed or secondary H1 markup."""
        content_php = json.dumps(
            str(
                REPO_PATH
                / 'wordpress'
                / 'wp-content'
                / 'themes'
                / 'vietnamguide-premium'
                / 'inc'
                / 'guide-content.php'
            ).replace('\\', '/')
        )
        result = self._run_php_harness(
            f"""
            function parse_blocks($content) {{
                return [['blockName' => null, 'innerHTML' => $content, 'innerContent' => [$content]]];
            }}
            function serialize_block($block) {{ return (string) ($block['innerHTML'] ?? ''); }}
            function serialize_blocks($blocks) {{
                return implode('', array_map('serialize_block', $blocks));
            }}
            require_once {content_php};

            // Case 1: Deeply nested <div> badges inside <div class="vg-guide-hero">
            $case1 = '<div class="vg-guide-hero"><div class="vg-hero-badge">Verified Field Guide</div><div class="vg-hero-meta"><div class="vg-hero-tag">Updated</div></div><h1>Ba Be Lake Travel Guide</h1></div><div class="vg-guide-body"><h2>Getting There</h2><p>Bus details.</p><h2>Where to Stay</h2><p>Homestays.</p></div>';
            $split1 = vg_split_guide_blocks($case1);
            echo is_array($split1) && strpos($split1['hero_source'], '<h1>Ba Be Lake Travel Guide</h1>') !== false && strpos($split1['body_source'], '<div class="vg-guide-body"><h2>Getting There</h2>') === 0 ? "1\\n" : "0\\n";

            // Case 2: <section class="vg-guide-hero"> containing internal <div> elements
            $case2 = '<section id="hero" class="vg-HeroWrapper vg-guide-hero"><div class="meta">Published</div><h1>Ha Giang Loop Guide</h1></section><h2>Route Overview</h2><p>Details.</p>';
            $split2 = vg_split_guide_blocks($case2);
            echo is_array($split2) && str_ends_with($split2['hero_source'], '</section>') && strpos($split2['body_source'], '<h2>Route Overview</h2>') === 0 ? "1\\n" : "0\\n";

            // Case 3: Custom attribute order and single-quoted class attribute
            $case3 = "<!-- wp:html -->\\n<div id=\\"custom-hero\\" data-vg=\\"true\\" class='vg-custom vg-guide-hero'><div><span>Inner</span></div><h1>Custom Attribute Order</h1></div><h2>Section One</h2><p>Body</p>";
            $split3 = vg_split_guide_blocks($case3);
            echo is_array($split3) && strpos($split3['hero_source'], '<h1>Custom Attribute Order</h1>') !== false ? "1\\n" : "0\\n";

            // Case 4: Unclosed outer hero container must fail closed (null)
            $case4 = '<div class="vg-guide-hero"><div class="inner">Badge</div><h1>Unclosed Hero</h1><h2>Body</h2>';
            echo vg_split_guide_blocks($case4) === null ? "1\\n" : "0\\n";

            // Case 5: Meaningful HTML before hero must fail closed (null)
            $case5 = '<p>Meaningful intro</p><div class="vg-guide-hero"><h1>Hero</h1></div><h2>Body</h2>';
            echo vg_split_guide_blocks($case5) === null ? "1\\n" : "0\\n";
            """
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.splitlines(), ['1', '1', '1', '1', '1'])

    def test_route_fallback_telemetry_emits_persistent_log_and_action_hook(self):
        """vg_record_route_fallback must persist sanitized single-line warnings to configured log sink and fire action hooks."""
        with tempfile.TemporaryDirectory(prefix='vg-fallback-log-') as tmp_dir:
            log_file = Path(tmp_dir) / 'route-fallback.log'
            log_file_php = json.dumps(str(log_file).replace('\\', '/'))
            result = self._run_php_harness(
                f"""
                define('VG_ROUTE_FALLBACK_LOG_FILE', {log_file_php});
                $GLOBALS['vg_hook_calls'] = [];
                function do_action($tag, ...$args) {{
                    $GLOBALS['vg_hook_calls'][] = ['tag' => $tag, 'args' => $args];
                }}
                vg_record_route_fallback("destinations/ba-be-lake\\n-injected", "invalid_guide\\r\\n_context\\"quote");
                echo count($GLOBALS['vg_hook_calls']), "\\n";
                echo $GLOBALS['vg_hook_calls'][0]['tag'], "\\n";
                """
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(result.stdout.splitlines(), ['1', 'vg_route_fallback_recorded'])
            self.assertTrue(log_file.is_file(), 'Fallback log file was not created')
            log_lines = log_file.read_text(encoding='utf-8').splitlines()
            self.assertEqual(len(log_lines), 1)
            self.assertIn('[VietnamGuide Route Fallback]', log_lines[0])
            self.assertIn('path="destinations/ba-be-lake -injected"', log_lines[0])
            self.assertIn('reason="invalid_guide  _context\'quote"', log_lines[0])

    def test_archive_directory_and_git_index_contain_zero_pyc_bytecode_files(self):
        """ops/archive-apply-scripts and git index must not contain compiled .pyc bytecode artifacts."""
        archive_dir = REPO_PATH / 'ops' / 'archive-apply-scripts'
        archive_pyc = list(archive_dir.rglob('*.pyc')) if archive_dir.is_dir() else []
        self.assertEqual(
            len(archive_pyc),
            0,
            f"Found {len(archive_pyc)} .pyc files in ops/archive-apply-scripts",
        )
        git_pyc = subprocess.run(
            ['git', 'ls-files', '*.pyc'],
            cwd=REPO_PATH,
            capture_output=True,
            text=True,
            check=False,
        )
        tracked_pyc = [line for line in git_pyc.stdout.splitlines() if line.strip()]
        self.assertEqual(tracked_pyc, [], f"Git index tracks .pyc files: {tracked_pyc}")

    def test_compiled_php_registry_parity_and_tamper_fallback(self):
        """Compiled PHP route registry in inc/guide-rollout.php must match JSON registry 100% and reject stale cache on SHA-256 mismatch."""
        from ops.route_contract import compile_php_registry

        json_path = (
            REPO_PATH
            / 'wordpress'
            / 'wp-content'
            / 'themes'
            / 'vietnamguide-premium'
            / 'inc'
            / 'guide-route-registry.json'
        )
        compiled_path = (
            REPO_PATH
            / 'wordpress'
            / 'wp-content'
            / 'themes'
            / 'vietnamguide-premium'
            / 'inc'
            / 'guide-rollout.php'
        )
        self.assertTrue(compiled_path.is_file(), f"Missing compiled PHP registry: {compiled_path}")
        expected_code = compile_php_registry(json_path)
        self.assertEqual(
            compiled_path.read_text(encoding='utf-8'),
            expected_code,
            'inc/guide-rollout.php is out of sync with inc/guide-route-registry.json',
        )

        # Verify runtime parity between compiled OPcache fast-path and forced JSON fallback
        res_fast = self._run_php_harness(
            """
            $reg = vg_guide_route_registry();
            echo json_encode($reg);
            """
        )
        res_json = self._run_php_harness(
            """
            define('VG_DISABLE_COMPILED_ROUTE_REGISTRY', true);
            $reg = vg_guide_route_registry();
            echo json_encode($reg);
            """
        )
        self.assertEqual(res_fast.returncode, 0, res_fast.stderr)
        self.assertEqual(res_json.returncode, 0, res_json.stderr)
        self.assertEqual(json.loads(res_fast.stdout), json.loads(res_json.stdout))

        # Verify cryptographic tamper detection: if JSON is corrupted while compiled PHP exists, it must fail closed
        with tempfile.TemporaryDirectory(prefix='vg-tamper-registry-') as tmp_dir:
            tamper_root = Path(tmp_dir)
            (tamper_root / 'inc').mkdir()
            (tamper_root / 'inc' / 'guide-route-registry.json').write_text(
                '{ corrupted json', encoding='utf-8'
            )
            res_tamper = self._run_php_harness(
                "echo count(vg_guide_route_registry()), \"\\n\";",
                theme_file_root=tamper_root,
            )
            self.assertEqual(res_tamper.returncode, 0, res_tamper.stderr)
            self.assertEqual(res_tamper.stdout.strip(), '0')

    def test_search_includes_pages_and_canonical_registry_matcher(self):
        """Site-wide search must include 'page' post_type via pre_get_posts and match canonical 282-route registry items."""
        res = self._run_php_harness(
            """
            class DummySearchQuery {
                public $post_types = [];
                public function is_main_query() { return true; }
                public function is_search() { return true; }
                public function get($key) { return null; }
                public function set($key, $val) { $this->post_types[$key] = $val; }
            }
            function is_admin() { return false; }
            $q = new DummySearchQuery();
            vg_include_pages_in_search_results($q);

            $matches_hanoi = vg_search_canonical_registry('Hanoi Old Quarter', 5);
            $matches_visa = vg_search_canonical_registry('Vietnam e-visa', 5);

            echo json_encode([
                'post_type'    => $q->post_types['post_type'] ?? [],
                'hanoi_count'  => count($matches_hanoi),
                'hanoi_top'    => $matches_hanoi[0]['path'] ?? '',
                'visa_count'   => count($matches_visa),
                'visa_top'     => $matches_visa[0]['path'] ?? '',
            ]);
            """
        )
        self.assertEqual(res.returncode, 0, res.stderr)
        data = json.loads(res.stdout)
        self.assertEqual(data['post_type'], ['page', 'post'])
        self.assertGreaterEqual(data['hanoi_count'], 1)
        self.assertIn('hanoi', data['hanoi_top'])
        self.assertGreaterEqual(data['visa_count'], 1)
        self.assertEqual(data['visa_top'], 'plan/vietnam-evisa')

    def test_newsletter_and_contact_conversion_handlers_and_forms(self):
        """Newsletter and contact backend handlers must validate inputs, reject honeypots, persist entries, and render live forms."""
        res = self._run_php_harness(
            """
            $GLOBALS['vg_options'] = [];
            function get_option($k, $default = []) { return $GLOBALS['vg_options'][$k] ?? $default; }
            function update_option($k, $v, $autoload = false) { $GLOBALS['vg_options'][$k] = $v; return true; }
            function esc_attr($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
            function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
            function esc_url($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
            function admin_url($p = '') { return 'https://vietnamguide.net/wp-admin/' . ltrim($p, '/'); }

            $sub_ok = vg_process_newsletter_subscription_payload('Reader@Example.com', '', 'newsletter');
            $sub_invalid = vg_process_newsletter_subscription_payload('not-an-email', '', 'newsletter');
            $sub_honeypot = vg_process_newsletter_subscription_payload('bot@example.com', 'https://spam.invalid', 'newsletter');

            $contact_ok = vg_process_contact_submission_payload(
                'Alex Mercer',
                'alex@example.com',
                'route_correction',
                'Express Bus 86 fare is 45,000 VND as verified this morning at Terminal 2.',
                ''
            );
            $contact_short = vg_process_contact_submission_payload(
                'Alex',
                'alex@example.com',
                'route_correction',
                'Too short',
                ''
            );

            $nl_form = vg_render_newsletter_subscription_form();
            $ct_form = vg_render_contact_dispatch_form();

            echo json_encode([
                'sub_ok'          => $sub_ok['ok'],
                'sub_invalid'     => $sub_invalid['ok'],
                'sub_hp_status'   => $sub_honeypot['status'],
                'stored_subs'     => count($GLOBALS['vg_options']['vg_newsletter_subscribers'] ?? []),
                'contact_ok'      => $contact_ok['ok'],
                'contact_short'   => $contact_short['ok'],
                'stored_contacts' => count($GLOBALS['vg_options']['vg_contact_submissions'] ?? []),
                'has_nl_form'     => strpos($nl_form, 'name="vg_subscriber_email"') !== false,
                'has_ct_form'     => strpos($ct_form, 'name="vg_contact_message"') !== false,
            ]);
            """
        )
        self.assertEqual(res.returncode, 0, res.stderr)
        data = json.loads(res.stdout)
        self.assertTrue(data['sub_ok'])
        self.assertFalse(data['sub_invalid'])
        self.assertEqual(data['sub_hp_status'], 'honeypot_discarded')
        self.assertEqual(data['stored_subs'], 1)
        self.assertTrue(data['contact_ok'])
        self.assertFalse(data['contact_short'])
        self.assertEqual(data['stored_contacts'], 1)
        self.assertTrue(data['has_nl_form'])
        self.assertTrue(data['has_ct_form'])


if __name__ == '__main__':
    unittest.main()


