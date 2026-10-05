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

        result = subprocess.run(
            ['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', str(verifier_path), '-FixturesOnly'],
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
            ['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', ps_test],
            cwd=str(REPO_PATH),
            capture_output=True,
            text=True,
            timeout=30,
        )
        self.assertEqual(result.returncode, 0, f"PowerShell batch test failed: {result.stdout}\n{result.stderr}")
        counts = result.stdout.strip().split(',')
        self.assertEqual(counts, ['87', '50', '50', '95', '282', '5'])


if __name__ == '__main__':
    unittest.main()
