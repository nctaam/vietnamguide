# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide SEO, Schema, Semantic Markup, and Agentic A11y.

Validates the full SEO implementation:
1. Snippet Controls: wp_robots filter, rank_math/frontend/robots, and meta fallback on public canonical routes vs defensive noindex on 404/search.
2. Schema Synchronization: Article.dateModified ISO 8601 formatting and sync with route registry records.
3. Google Discover Threshold: Article.image contains an ImageObject with width >= 1200px.
4. Author E-E-A-T: Person profile url (/editorial-policy/#team) and worksFor linkage to #organization.
5. Semantic Tables: Injection of <caption>, <th scope="col"> in thead, and <th scope="row"> on first cell of tbody rows.
6. Image SEO: Contextual fallback alt text generation on empty or missing alt attributes.
7. AI Crawler Directives: Removal of non-standard LLMs-Txt: from robots.txt while retaining rewrite endpoints.
8. Internal Link Graph: Zero orphan routes across all 282 routes in ops/route_registry.json.
9. Agentic Accessibility Tree: Live status announcers, aria-pressed toggles, heading hierarchy, and zero SVG pin focus trap.
10. Template Breadcrumbs & CWV: Semantic breadcrumb nav and fetchpriority="high" on hero images.
"""
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import unittest

REPO_ROOT = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..')))
if str(REPO_ROOT) not in sys.path:
    sys.path.insert(0, str(REPO_ROOT))

from ops.compile_route_registry import compile_route_registry
from ops.tariff_sentinel import audit_route_registry, audit_registry_parity, audit_travel_clusters

THEME_INC = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'inc'
THEME_DIR = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium'
MU_PLUGIN = REPO_ROOT / 'wordpress' / 'wp-content' / 'mu-plugins' / 'vietnamguide-core.php'
ROUTE_REGISTRY_JSON = REPO_ROOT / 'ops' / 'route_registry.json'
THEME_REGISTRY_JSON = THEME_INC / 'guide-route-registry.json'
THEME_ROLLOUT_PHP = THEME_INC / 'guide-rollout.php'
GUIDE_SEO_PHP = THEME_INC / 'guide-seo.php'
GUIDE_AIO_PHP = THEME_INC / 'guide-aio.php'
IMAGE_DIMS_PHP = THEME_INC / 'image-dimensions.php'
INTERACTIVE_MAP_PHP = THEME_INC / 'guide-interactive-map.php'
PHOTO_DISPATCH_PHP = THEME_INC / 'guide-photo-dispatch.php'
GUIDE_CONTEXT_PHP = THEME_INC / 'guide-context.php'
VISA_CHECKER_PHP = THEME_INC / 'guide-visa-checker.php'
COST_CALC_PHP = THEME_INC / 'guide-cost-calculator.php'
GUIDE_PAGE_PHP = THEME_DIR / 'template-parts' / 'guide-page.php'
GUIDE_EXP_CSS = THEME_DIR / 'assets' / 'css' / 'guide-experience.css'


def _build_php_harness(php_body: str) -> str:
    """Build a standalone PHP harness script with complete WordPress environment mocks."""
    mu_path = str(MU_PLUGIN).replace('\\', '/')
    seo_path = str(GUIDE_SEO_PHP).replace('\\', '/')
    aio_path = str(GUIDE_AIO_PHP).replace('\\', '/')
    dims_path = str(IMAGE_DIMS_PHP).replace('\\', '/')
    routing_path = str(THEME_INC / 'guide-routing.php').replace('\\', '/')
    theme_path = str(THEME_DIR).replace('\\', '/')

    return f"""<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

define('ABSPATH', 1);

if (! function_exists('__return_false')) {{
    function __return_false(): bool {{ return false; }}
}}

if (! class_exists('WP_Post')) {{
    class WP_Post {{
        public $ID = 1;
        public $post_type = 'page';
        public $post_name = 'vietnam-travel-cost';
        public $post_title = 'Vietnam Travel Cost: Complete Daily Budget Breakdown 2026';
        public $post_content = '';
        public $post_status = 'publish';
    }}
}}

if (! class_exists('WP_Term')) {{
    class WP_Term {{
        public $term_id = 1;
        public $slug = '';
        public $taxonomy = 'category';
    }}
}}

if (! class_exists('WP_HTML_Tag_Processor')) {{
    class WP_HTML_Tag_Processor {{
        private $html;
        private $tokens = [];
        private $index = -1;

        public function __construct($html) {{
            $this->html = (string) $html;
            preg_match_all('/<[^>]+>|[^<]+/s', $this->html, $m);
            foreach ($m[0] as $token) {{
                $is_tag = str_starts_with($token, '<');
                $this->tokens[] = [
                    'raw'    => $token,
                    'is_tag' => $is_tag,
                ];
            }}
        }}

        public function next_token(): bool {{
            $this->index++;
            return isset($this->tokens[$this->index]);
        }}

        public function get_token_type(): ?string {{
            if (! isset($this->tokens[$this->index])) {{
                return null;
            }}
            return $this->tokens[$this->index]['is_tag'] ? '#tag' : '#text';
        }}

        public function next_tag($tag_query = null): bool {{
            while ($this->next_token()) {{
                if ($this->tokens[$this->index]['is_tag']) {{
                    $raw = $this->tokens[$this->index]['raw'];
                    if (preg_match('/^<\\/?([a-zA-Z0-9_-]+)/', $raw, $m)) {{
                        $tag = strtoupper($m[1]);
                        if ($tag_query === null || (is_string($tag_query) && strtoupper($tag_query) === $tag)) {{
                            return true;
                        }}
                    }}
                }}
            }}
            return false;
        }}

        public function get_tag(): string {{
            if (! isset($this->tokens[$this->index]) || ! $this->tokens[$this->index]['is_tag']) {{
                return '';
            }}
            preg_match('/^<\\/?([a-zA-Z0-9_-]+)/', $this->tokens[$this->index]['raw'], $m);
            return strtoupper($m[1] ?? '');
        }}

        public function is_tag_closer(): bool {{
            if (! isset($this->tokens[$this->index]) || ! $this->tokens[$this->index]['is_tag']) {{
                return false;
            }}
            return str_starts_with($this->tokens[$this->index]['raw'], '</');
        }}

        public function get_attribute(string $name): ?string {{
            if (! isset($this->tokens[$this->index]) || ! $this->tokens[$this->index]['is_tag']) {{
                return null;
            }}
            $raw = $this->tokens[$this->index]['raw'];
            $pat_dq = '/' . preg_quote($name, '/') . '\\s*=\\s*"([^"]*)"/i';
            $pat_sq = "/" . preg_quote($name, "/") . "\\s*=\\s*'([^']*)'/i";
            $pat_uq = '/' . preg_quote($name, '/') . '\\s*=\\s*([^\\s>]+)/i';

            if (preg_match($pat_dq, $raw, $m)) {{
                return $m[1];
            }}
            if (preg_match($pat_sq, $raw, $m)) {{
                return $m[1];
            }}
            if (preg_match($pat_uq, $raw, $m)) {{
                return $m[1];
            }}
            return null;
        }}

        public function set_attribute(string $name, string $value): void {{
            if (! isset($this->tokens[$this->index]) || ! $this->tokens[$this->index]['is_tag']) {{
                return;
            }}
            $raw = &$this->tokens[$this->index]['raw'];
            $val_esc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $attr_str = $name . '="' . $val_esc . '"';

            $pat_dq = '/' . preg_quote($name, '/') . '\\s*=\\s*"[^"]*"/i';
            $pat_sq = "/" . preg_quote($name, "/") . "\\s*=\\s*'[^']*'/i";
            $pat_uq = '/' . preg_quote($name, '/') . '\\s*=\\s*[^\\s>]+/i';

            if (preg_match($pat_dq, $raw)) {{
                $raw = preg_replace($pat_dq, $attr_str, $raw, 1);
            }} elseif (preg_match($pat_sq, $raw)) {{
                $raw = preg_replace($pat_sq, $attr_str, $raw, 1);
            }} elseif (preg_match($pat_uq, $raw)) {{
                $raw = preg_replace($pat_uq, $attr_str, $raw, 1);
            }} else {{
                $raw = preg_replace('/>$/', ' ' . $attr_str . '>', $raw, 1);
            }}
        }}

        public function has_class(string $class_name): bool {{
            $class_attr = $this->get_attribute('class');
            if (! $class_attr) {{
                return false;
            }}
            $classes = preg_split('/\\s+/', trim($class_attr));
            return in_array($class_name, $classes, true);
        }}

        public function get_updated_html(): string {{
            $out = '';
            foreach ($this->tokens as $t) {{
                $out .= $t['raw'];
            }}
            return $out;
        }}
    }}
}}

$GLOBALS['vg_mock_filters'] = [];
$GLOBALS['vg_mock_actions'] = [];

function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {{
    $GLOBALS['vg_mock_filters'][$tag][] = [
        'callback' => $callback,
        'priority' => $priority,
        'args'     => $accepted_args,
    ];
}}

function apply_filters($tag, $value, ...$args) {{
    if (! isset($GLOBALS['vg_mock_filters'][$tag])) {{
        return $value;
    }}
    $filters = $GLOBALS['vg_mock_filters'][$tag];
    usort($filters, function($a, $b) {{ return $a['priority'] <=> $b['priority']; }});
    foreach ($filters as $f) {{
        $pass_args = array_merge([$value], $args);
        $pass_args = array_slice($pass_args, 0, $f['args']);
        $value = call_user_func_array($f['callback'], $pass_args);
    }}
    return $value;
}}

function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {{
    $GLOBALS['vg_mock_actions'][$tag][] = [
        'callback' => $callback,
        'priority' => $priority,
        'args'     => $accepted_args,
    ];
}}

function do_action($tag, ...$args) {{
    if (! isset($GLOBALS['vg_mock_actions'][$tag])) {{
        return;
    }}
    $actions = $GLOBALS['vg_mock_actions'][$tag];
    usort($actions, function($a, $b) {{ return $a['priority'] <=> $b['priority']; }});
    foreach ($actions as $act) {{
        $pass_args = array_slice($args, 0, $act['args']);
        call_user_func_array($act['callback'], $pass_args);
    }}
}}

function add_shortcode($tag, $callback) {{}}
function shortcode_atts($pairs, $atts, $shortcode = '') {{
    $atts = (array)$atts;
    $out = [];
    foreach ($pairs as $name => $default) {{
        $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
    }}
    return $out;
}}

$GLOBALS['vg_is_singular'] = true;
$GLOBALS['vg_is_404'] = false;
$GLOBALS['vg_is_search'] = false;
$GLOBALS['vg_is_front_page'] = false;
$GLOBALS['vg_is_home'] = false;

function is_singular($post_types = '') {{ return $GLOBALS['vg_is_singular'] ?? true; }}
function is_front_page() {{ return $GLOBALS['vg_is_front_page'] ?? false; }}
function is_home() {{ return $GLOBALS['vg_is_home'] ?? false; }}
function is_404() {{ return $GLOBALS['vg_is_404'] ?? false; }}
function is_search() {{ return $GLOBALS['vg_is_search'] ?? false; }}
function get_post($id = null) {{ return $GLOBALS['post'] ?? new WP_Post(); }}
function is_category() {{ return false; }}
function is_tag() {{ return false; }}
function get_queried_object() {{ return $GLOBALS['post'] ?? null; }}
function get_queried_object_id() {{ return $GLOBALS['vg_test_qid'] ?? 1; }}
function get_permalink($id = 0) {{ return 'https://vietnamguide.net/' . ($GLOBALS['post']->post_name ?? ''); }}
function home_url($p = '') {{ return 'https://vietnamguide.net' . $p; }}
function untrailingslashit($val) {{ return rtrim((string)$val, '/'); }}
function esc_attr($s) {{ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }}
function esc_html($s) {{ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }}
function esc_url($s) {{ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }}
function esc_js($s) {{ return addslashes((string)$s); }}
function esc_attr__($s, $d = '') {{ return $s; }}
function esc_attr_e($s, $d = '') {{ echo $s; }}
function esc_html_e($s, $d = '') {{ echo $s; }}
function wp_strip_all_tags($s) {{ return strip_tags((string)$s); }}
function get_option($k, $d = '') {{ return $d; }}
function wp_safe_redirect($t, $s = 302) {{}}
function __($s, $d = '') {{ return $s; }}
function get_stylesheet_directory_uri() {{ return 'https://vietnamguide.net/wp-content/themes/vietnamguide-premium'; }}
function get_theme_file_uri($p = '') {{ return 'https://vietnamguide.net/wp-content/themes/vietnamguide-premium' . $p; }}
function get_theme_file_path($p = '') {{ return '{theme_path}' . $p; }}
function sanitize_key($k) {{ return preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$k)); }}
function sanitize_text_field($t) {{ return trim(strip_tags((string)$t)); }}
function wp_json_encode($data, $options = 0, $depth = 512) {{ return json_encode($data, $options, $depth); }}

require_once '{mu_path}';
require_once '{dims_path}';
require_once '{routing_path}';
require_once '{seo_path}';
require_once '{aio_path}';

{php_body}
"""


def _run_php(script_body: str) -> subprocess.CompletedProcess:
    """Execute PHP script body inside the standalone WordPress harness."""
    full_script = _build_php_harness(script_body)
    with tempfile.NamedTemporaryFile('w', suffix='.php', encoding='utf-8', delete=False) as handle:
        handle.write(full_script)
        script_path = handle.name

    try:
        return subprocess.run(
            ['php', script_path],
            cwd=str(REPO_ROOT),
            capture_output=True,
            text=True,
            check=False,
        )
    finally:
        Path(script_path).unlink(missing_ok=True)


class TestGuideSEO(unittest.TestCase):
    """Unit and Contract Tests for VietnamGuide SEO & Accessibility Engine."""

    @classmethod
    def setUpClass(cls):
        with open(ROUTE_REGISTRY_JSON, 'r', encoding='utf-8') as f:
            cls.registry_data = json.load(f)
            cls.routes = cls.registry_data.get('routes', [])

    def setUp(self):
        self.routes = self.__class__.routes

    def test_wp_robots_filter_canonical_public_routes(self):
        """Canonical public routes must receive max-snippet:-1, max-image-preview:large, max-video-preview:-1."""
        php_code = """
        $GLOBALS['vg_is_singular'] = true;
        $GLOBALS['vg_is_404'] = false;
        $GLOBALS['vg_is_search'] = false;

        $wp_robots = apply_filters('wp_robots', []);
        $rm_robots = apply_filters('rank_math/frontend/robots', []);

        ob_start();
        do_action('wp_head');
        $head_output = ob_get_clean();

        echo json_encode([
            'wp_robots'   => $wp_robots,
            'rm_robots'   => $rm_robots,
            'head_output' => $head_output,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}\n{res.stdout}")
        data = json.loads(res.stdout)

        # Check wp_robots filter
        wp = data['wp_robots']
        self.assertTrue(wp.get('index'), "wp_robots must set index=true on canonical routes")
        self.assertTrue(wp.get('follow'), "wp_robots must set follow=true on canonical routes")
        self.assertEqual(wp.get('max-snippet'), -1, "wp_robots must specify max-snippet=-1")
        self.assertEqual(wp.get('max-image-preview'), 'large', "wp_robots must specify max-image-preview=large")
        self.assertEqual(wp.get('max-video-preview'), -1, "wp_robots must specify max-video-preview=-1")

        # Check Rank Math filter
        rm = data['rm_robots']
        self.assertEqual(rm.get('index'), 'index')
        self.assertEqual(rm.get('follow'), 'follow')
        self.assertEqual(rm.get('max-snippet'), 'max-snippet:-1')
        self.assertEqual(rm.get('max-image-preview'), 'max-image-preview:large')
        self.assertEqual(rm.get('max-video-preview'), 'max-video-preview:-1')

        # Check fallback meta output
        head = data['head_output']
        self.assertIn('name="robots"', head)
        self.assertIn('max-snippet:-1', head)
        self.assertIn('max-image-preview:large', head)
        self.assertIn('max-video-preview:-1', head)

    def test_defensive_noindex_on_404_and_search(self):
        """Defensive indexing: 404 routes must emit noindex, nofollow; search routes must emit noindex, follow."""
        # 1. Test 404
        php_404 = """
        $GLOBALS['vg_is_singular'] = false;
        $GLOBALS['vg_is_404'] = true;
        $GLOBALS['vg_is_search'] = false;

        $wp_robots = apply_filters('wp_robots', []);
        $rm_robots = apply_filters('rank_math/frontend/robots', []);

        ob_start();
        do_action('wp_head');
        $head_output = ob_get_clean();

        echo json_encode([
            'wp_robots'   => $wp_robots,
            'rm_robots'   => $rm_robots,
            'head_output' => $head_output,
        ]);
        """
        res_404 = _run_php(php_404)
        self.assertEqual(res_404.returncode, 0, f"PHP 404 error:\n{res_404.stderr}")
        data_404 = json.loads(res_404.stdout)
        self.assertTrue(data_404['wp_robots'].get('noindex'), "404 must have noindex: true")
        self.assertTrue(data_404['wp_robots'].get('nofollow'), "404 must have nofollow: true")
        self.assertEqual(data_404['rm_robots'].get('noindex'), 'noindex')
        self.assertEqual(data_404['rm_robots'].get('nofollow'), 'nofollow')
        self.assertIn('<meta name="robots" content="noindex, nofollow">', data_404['head_output'])

        # 2. Test search
        php_search = """
        $GLOBALS['vg_is_singular'] = false;
        $GLOBALS['vg_is_404'] = false;
        $GLOBALS['vg_is_search'] = true;

        $wp_robots = apply_filters('wp_robots', []);
        $rm_robots = apply_filters('rank_math/frontend/robots', []);

        ob_start();
        do_action('wp_head');
        $head_output = ob_get_clean();

        echo json_encode([
            'wp_robots'   => $wp_robots,
            'rm_robots'   => $rm_robots,
            'head_output' => $head_output,
        ]);
        """
        res_search = _run_php(php_search)
        self.assertEqual(res_search.returncode, 0, f"PHP search error:\n{res_search.stderr}")
        data_search = json.loads(res_search.stdout)
        self.assertTrue(data_search['wp_robots'].get('noindex'), "Search must have noindex: true")
        self.assertTrue(data_search['wp_robots'].get('follow'), "Search must have follow: true")
        self.assertEqual(data_search['rm_robots'].get('noindex'), 'noindex')
        self.assertEqual(data_search['rm_robots'].get('nofollow'), 'follow')
        self.assertIn('<meta name="robots" content="noindex, follow">', data_search['head_output'])

    def test_schema_article_date_modified_iso8601_sync(self):
        """Article.dateModified must be valid ISO 8601 and synchronized with registry reviewed_at/last_reviewed."""
        php_code = """
        $route = '/itineraries/10-days-in-vietnam/';
        $post = new WP_Post();
        $post->post_name = 'itineraries/10-days-in-vietnam';
        $post->post_title = '10 Days in Vietnam: Best Route for First-Time Visitors';
        $GLOBALS['post'] = $post;

        $_SERVER['REQUEST_URI'] = $route;

        $initial_data = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'Article',
                    '@id'   => 'https://vietnamguide.net' . $route . '#article',
                ],
            ],
        ];

        $enriched = apply_filters('rank_math/json_ld', $initial_data, null);
        echo json_encode($enriched);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}\n{res.stdout}")
        schema = json.loads(res.stdout)

        graph = schema.get('@graph', [])
        article_nodes = [node for node in graph if node.get('@type') == 'Article']
        self.assertTrue(len(article_nodes) > 0, "Schema graph must contain an Article node")
        article = article_nodes[0]

        # Verify dateModified
        date_modified = article.get('dateModified')
        self.assertIsNotNone(date_modified, "Article.dateModified must exist")
        iso_pattern = r'^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$'
        self.assertRegex(
            date_modified,
            iso_pattern,
            f"dateModified '{date_modified}' must conform strictly to ISO 8601 with timezone offset",
        )

        # Verify synchronization with registry
        reg_record = None
        for item in self.routes:
            p = item.get('path', '')
            if p == 'itineraries/10-days-in-vietnam' or p == '/itineraries/10-days-in-vietnam/':
                reg_record = item
                break
        self.assertIsNotNone(reg_record, "Route record must exist in registry")
        expected_date = reg_record.get('reviewed_at') or reg_record.get('last_reviewed')
        self.assertTrue(date_modified.startswith(expected_date), f"dateModified {date_modified} must sync with registry date {expected_date}")

    def test_schema_google_discover_image_threshold(self):
        """Article.image must contain an ImageObject with width >= 1200px and height >= 675px for Google Discover eligibility."""
        php_code = """
        $route = '/costs/vietnam-travel-cost/';
        $post = new WP_Post();
        $post->post_name = 'vietnam-travel-cost';
        $GLOBALS['post'] = $post;
        $_SERVER['REQUEST_URI'] = $route;

        $initial_data = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'Article',
                    '@id'   => 'https://vietnamguide.net' . $route . '#article',
                ],
            ],
        ];

        $enriched = apply_filters('rank_math/json_ld', $initial_data, null);
        echo json_encode($enriched);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        schema = json.loads(res.stdout)

        graph = schema.get('@graph', [])
        article_nodes = [node for node in graph if node.get('@type') == 'Article']
        self.assertTrue(len(article_nodes) > 0)
        article = article_nodes[0]

        img = article.get('image')
        self.assertIsNotNone(img, "Article.image must be populated")
        if isinstance(img, dict):
            img_obj = img
        elif isinstance(img, list) and len(img) > 0 and isinstance(img[0], dict):
            img_obj = img[0]
        else:
            self.fail(f"Article.image must be an ImageObject dict, got: {type(img)}")

        self.assertEqual(img_obj.get('@type'), 'ImageObject', "Image must declare @type => ImageObject")
        width = int(img_obj.get('width', 0))
        height = int(img_obj.get('height', 0))
        self.assertGreaterEqual(
            width, 1200,
            f"Google Discover image threshold requires width >= 1200px, got {width}px"
        )
        self.assertGreaterEqual(
            height, 675,
            f"Google Discover image threshold requires height >= 675px, got {height}px"
        )
        self.assertTrue(img_obj.get('url', '').startswith('https://'), "Image URL must be secure HTTPS")

    def test_author_eeat_profile_and_works_for_linkage(self):
        """Author and reviewedBy in Schema JSON-LD must contain profile URL and worksFor linkage to #organization."""
        php_code = """
        $route = '/costs/vietnam-travel-cost/';
        $post = new WP_Post();
        $post->post_name = 'vietnam-travel-cost';
        $GLOBALS['post'] = $post;
        $_SERVER['REQUEST_URI'] = $route;

        $initial_data = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'Article',
                    '@id'   => 'https://vietnamguide.net' . $route . '#article',
                    'author' => [
                        '@type' => 'Person',
                        'name'  => 'VietnamGuide editorial team',
                    ],
                ],
                [
                    '@type' => 'Person',
                    '@id'   => 'https://vietnamguide.net/#person',
                    'name'  => 'VietnamGuide editorial team',
                ],
            ],
        ];

        $enriched = apply_filters('rank_math/json_ld', $initial_data, null);
        echo json_encode($enriched);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        schema = json.loads(res.stdout)
        graph = schema.get('@graph', [])

        # Check Person author node in graph
        person_nodes = [node for node in graph if node.get('@type') == 'Person']
        self.assertTrue(len(person_nodes) > 0, "Schema graph must contain a Person author node")
        person = person_nodes[0]

        # Author profile url
        self.assertIn('url', person, "Person node must have a profile URL")
        self.assertTrue(
            person['url'].endswith('/editorial-policy/#team') or person['url'].endswith('/editorial-policy/'),
            f"Person URL must point to editorial policy, got {person['url']}",
        )

        # worksFor linkage
        self.assertIn('worksFor', person, "Person node must link to worksFor")
        self.assertEqual(person['worksFor'].get('@type'), 'Organization')
        self.assertTrue(
            str(person['worksFor'].get('@id', '')).endswith('#organization'),
            "worksFor @id must reference the publisher #organization",
        )

        # reviewedBy in Article
        article = [node for node in graph if node.get('@type') == 'Article'][0]
        reviewed_by = article.get('reviewedBy')
        self.assertIsNotNone(reviewed_by, "Article must specify reviewedBy")
        self.assertTrue(
            str(reviewed_by.get('url', '')).endswith('/editorial-policy/'),
            f"reviewedBy url must point to /editorial-policy/, got {reviewed_by.get('url')}",
        )
        self.assertIn('worksFor', reviewed_by)
        self.assertTrue(str(reviewed_by['worksFor'].get('@id', '')).endswith('#organization'))

    def test_semantic_table_enhancements(self):
        """vg_enhance_table_markup must inject <caption>, <th scope="col">, and <th scope="row">."""
        php_code = """
        $input_table = '<table>' .
            '<thead><tr><th>Route Segment</th><th>Transit Time</th><th>Cost</th></tr></thead>' .
            '<tbody>' .
            '<tr><td>Hanoi to Ha Giang</td><td>6 hours</td><td>$14</td></tr>' .
            '<tr><td>Ha Giang to Dong Van</td><td>4 hours</td><td>$8</td></tr>' .
            '</tbody>' .
            '</table>';

        $output = vg_enhance_table_markup($input_table);
        echo json_encode(['html' => $output]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out_html = json.loads(res.stdout)['html']

        # 1. Must contain <caption>
        self.assertIn('<caption', out_html, "Enhanced table must inject a <caption>")
        # 2. Must contain <th scope="col"> in thead
        self.assertIn('<th scope="col">Route Segment</th>', out_html)
        self.assertIn('<th scope="col">Transit Time</th>', out_html)
        self.assertIn('<th scope="col">Cost</th>', out_html)
        # 3. Must inject <th scope="row"> in first cell of tbody tr
        self.assertIn('<th scope="row">Hanoi to Ha Giang</th>', out_html)
        self.assertIn('<th scope="row">Ha Giang to Dong Van</th>', out_html)
        # 4. Subsequent cells in tbody must remain <td>
        self.assertIn('<td>6 hours</td>', out_html)
        self.assertIn('<td>$14</td>', out_html)

    def test_contextual_fallback_alt_on_content_images(self):
        """vg_enhance_content_images must provide contextual fallback alt text when missing or empty."""
        php_code = """
        $post = new WP_Post();
        $post->post_title = 'Ha Long Bay Cruise Guide';
        $GLOBALS['post'] = $post;

        $input_html = '<div>' .
            '<img src="/assets/images/halong-bay.jpg">' .
            '<img src="/assets/images/titov-island.jpg" alt="">' .
            '<img src="/assets/images/sung-sot-cave.jpg" alt="Interior stalactites of Sung Sot Cave">' .
            '</div>';

        $output = vg_enhance_content_images($input_html);
        echo json_encode(['html' => $output]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out_html = json.loads(res.stdout)['html']

        # The image without alt must now have an alt derived contextually
        self.assertNotIn('<img src="/assets/images/halong-bay.jpg">', out_html)
        self.assertIn('alt="Halong Bay - Vietnam Travel Field Guide"', out_html)
        # The image with empty alt="" must also be populated
        self.assertNotIn('alt=""', out_html)
        self.assertIn('alt="Titov Island - Vietnam Travel Field Guide"', out_html)
        # The image with existing non-empty alt must be preserved verbatim
        self.assertIn('alt="Interior stalactites of Sung Sot Cave"', out_html)

    def test_robots_txt_ai_crawler_directives_no_llms_txt_header(self):
        """Robots.txt must NOT emit non-standard LLMs-Txt: lines while preserving valid AI crawler directives."""
        php_code = """
        $initial = "User-agent: *\\nDisallow: /wp-admin/\\n";
        $robots = vg_add_ai_crawler_directives($initial, true);
        echo json_encode(['robots' => $robots]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        robots_txt = json.loads(res.stdout)['robots']

        # Non-standard directives must be removed
        self.assertNotIn('LLMs-Txt:', robots_txt, "robots.txt must NOT contain non-standard 'LLMs-Txt:' header")
        self.assertNotIn('LLMs-Full-Txt:', robots_txt, "robots.txt must NOT contain non-standard 'LLMs-Full-Txt:' header")

        # Valid AI user-agents must be defined
        self.assertIn('User-agent: GPTBot', robots_txt)
        self.assertIn('User-agent: ClaudeBot', robots_txt)
        self.assertIn('User-agent: PerplexityBot', robots_txt)
        self.assertIn('User-agent: Google-Extended', robots_txt)
        self.assertIn('User-agent: Applebot-Extended', robots_txt)
        self.assertIn('Allow: /', robots_txt)

        # Virtual rewrite endpoints for /llms.txt and /llms-full.txt must exist in guide-aio.php
        with open(GUIDE_AIO_PHP, 'r', encoding='utf-8') as f:
            aio_src = f.read()
        self.assertIn('llms.txt', aio_src, "guide-aio.php must handle /llms.txt virtual endpoint")
        self.assertIn('llms-full.txt', aio_src, "guide-aio.php must handle /llms-full.txt virtual endpoint")

    def test_internal_link_graph_zero_orphan_routes(self):
        """Internal link graph must contain 282 valid routes with 0 orphans and strict SHA-256 byte parity."""
        # 1. File parity check
        with open(ROUTE_REGISTRY_JSON, 'rb') as f1, open(THEME_REGISTRY_JSON, 'rb') as f2:
            h1 = hashlib.sha256(f1.read()).hexdigest()
            h2 = hashlib.sha256(f2.read()).hexdigest()
        self.assertEqual(h1, h2, "ops/route_registry.json and inc/guide-route-registry.json must have 100% SHA-256 byte parity")

        # 2. Total route count
        self.assertEqual(len(self.routes), 282, "Registry must contain exactly 282 canonical routes")

        # 3. Graph connectivity and orphan check
        valid_parents = {'destinations', 'itineraries', 'compare', 'plan'}

        orphans = []
        for r in self.routes:
            path = r.get('path', '')
            parent = r.get('parent')

            if not path:
                orphans.append(f"Invalid empty path in route: {r}")
            elif parent not in valid_parents:
                orphans.append(f"Orphan route without valid parent category: {path} (parent={parent})")

        self.assertEqual(len(orphans), 0, f"Found orphan routes in internal link graph: {orphans}")

    def test_agentic_accessibility_tree(self):
        """Interactive components must declare role=status, aria-live=polite, aria-pressed, and level-3 headings."""
        # 1. Interactive map
        with open(INTERACTIVE_MAP_PHP, 'r', encoding='utf-8') as f:
            map_code = f.read()
        self.assertIn('role="heading" aria-level="3"', map_code, "Interactive map title must use aria-level=3")
        self.assertIn('id="vg-map-aria-status"', map_code)
        self.assertIn('role="status"', map_code)
        self.assertIn('aria-live="polite"', map_code)
        self.assertIn('<noscript>', map_code, "Interactive map must provide <noscript> accessible fallback")
        self.assertIn('<th scope="row">', map_code, "<noscript> table must have <th scope='row'> headers")
        # Ensure SVG pins do NOT have interactive tabindex="0" (prevents focus traps)
        svg_pin_matches = re.findall(r'<g[^>]+class="[^"]*vg-map-pin[^"]*"[^>]*tabindex=', map_code)
        self.assertEqual(len(svg_pin_matches), 0, "SVG pins must not have tabindex attributes when parent is aria-hidden")

        # 2. Photo dispatch
        with open(PHOTO_DISPATCH_PHP, 'r', encoding='utf-8') as f:
            dispatch_code = f.read()
        self.assertIn('role="heading" aria-level="3"', dispatch_code, "Photo dispatch title must use aria-level=3")
        self.assertIn('id="vg-dispatch-aria-status"', dispatch_code)
        self.assertIn('role="status"', dispatch_code)
        self.assertIn('aria-live="polite"', dispatch_code)

        # 3. Visa checker
        with open(VISA_CHECKER_PHP, 'r', encoding='utf-8') as f:
            visa_code = f.read()
        self.assertIn('role="heading" aria-level="3"', visa_code, "Visa checker title must use aria-level=3")
        self.assertIn('aria-pressed=', visa_code, "Visa checker entry mode buttons must declare aria-pressed")
        self.assertIn('id="vg-vc-results"', visa_code)
        self.assertIn('role="status"', visa_code)
        self.assertIn('aria-live="polite"', visa_code)

        # 4. Cost calculator
        with open(COST_CALC_PHP, 'r', encoding='utf-8') as f:
            calc_code = f.read()
        self.assertIn('role="heading" aria-level="3"', calc_code, "Cost calculator title must use aria-level=3")
        self.assertIn('class="vg-calc-results" role="status" aria-live="polite"', calc_code)
        self.assertIn('id="vg-calc-aria-status"', calc_code)
        self.assertIn('aria-pressed=', calc_code, "Cost calculator buttons/pills must declare aria-pressed")

        # Invariant: 0 <h2> tags across all interactive components
        for comp_name, src in [('map', map_code), ('dispatch', dispatch_code), ('visa', visa_code), ('calc', calc_code)]:
            h2_matches = re.findall(r'<h2\b', src, re.IGNORECASE)
            self.assertEqual(len(h2_matches), 0, f"{comp_name} must have 0 <h2> tags")

    def test_template_parts_guide_page_semantics_and_cwv(self):
        """guide-page.php must render semantic HTML breadcrumbs matching JSON-LD, guard against duplicate breadcrumbs, and set fetchpriority=high on hero."""
        with open(GUIDE_PAGE_PHP, 'r', encoding='utf-8') as f:
            gp_code = f.read()

        # Semantic breadcrumbs + deduplication guard when $heroHtml already contains a Breadcrumb nav
        self.assertIn('stripos($heroHtml, \'aria-label="Breadcrumb\') === false', gp_code)
        self.assertIn('class="vg-guide-breadcrumb"', gp_code)
        self.assertIn('aria-label="<?php esc_attr_e(\'Breadcrumbs\', \'vietnamguide-premium\'); ?>"', gp_code)
        self.assertIn('class="vg-breadcrumbs-list"', gp_code)
        self.assertIn('class="vg-breadcrumb-item"', gp_code)

        # Horizontal flex list styling for .vg-breadcrumbs-list in guide-experience.css
        with open(GUIDE_EXP_CSS, 'r', encoding='utf-8') as f:
            css_code = f.read()
        self.assertIn('.vg-breadcrumbs-list', css_code)
        self.assertIn('list-style: none', css_code)

        # LCP Hero fetchpriority
        self.assertIn('fetchpriority="high"', gp_code, "Hero cover image must have fetchpriority='high' for CWV LCP optimization")

    def test_serp_title_and_description_golden_window_100_percent(self):
        """All 282 routes must have titles <= 60 chars and meta descriptions strictly within the 147-157 char Google SERP Golden Window."""
        title_violations = []
        desc_violations = []
        for r in self.routes:
            path = r.get('path', '')
            title = r.get('title', '')
            desc = r.get('description', '')
            if not (1 <= len(title) <= 60):
                title_violations.append((path, len(title), title))
            if not (147 <= len(desc) <= 157):
                desc_violations.append((path, len(desc), desc))

        self.assertEqual(len(title_violations), 0, f"Found route titles exceeding 60 chars: {title_violations}")
        self.assertEqual(len(desc_violations), 0, f"Found route descriptions outside 147-157 char Golden Window: {desc_violations}")

    def test_guide_context_object_caching_contract(self):
        """vg_build_guide_context must cache its derived route context via wp_cache_get / wp_cache_set in group vietnamguide_context."""
        with open(GUIDE_CONTEXT_PHP, 'r', encoding='utf-8') as f:
            ctx_code = f.read()
        self.assertIn("wp_cache_get($cacheKey, 'vietnamguide_context'", ctx_code)
        self.assertIn("wp_cache_set($cacheKey, $context, 'vietnamguide_context'", ctx_code)

    def test_canonical_registry_title_description_and_schema_headline_sync(self):
        """Canonical registry title (<=60 chars) and Golden Window description (147-157 chars) must override stale Rank Math DB values across document title, meta description, OpenGraph, and JSON-LD schema."""
        php_code = r"""
$_SERVER['REQUEST_URI'] = '/destinations/mang-den-travel-guide/';
$p = new WP_Post();
$p->ID = 777;
$p->post_type = 'page';
$p->post_name = 'mang-den-travel-guide';
$p->post_title = 'Mang Den Travel Guide 2026: Cool Highland Pines, Waterfalls & Kon Tum';
$GLOBALS['post'] = $p;

$filtered_doc_title = apply_filters('pre_get_document_title', 'Stale Long Title From Database Exceeding Sixty Characters');
$filtered_rm_title  = apply_filters('rank_math/frontend/title', 'Stale Rank Math Title');
$filtered_og_title  = apply_filters('rank_math/opengraph/facebook/title', 'Stale OG Title');
$filtered_rm_desc   = apply_filters('rank_math/frontend/description', 'Stale short description.');
$filtered_tw_desc   = apply_filters('rank_math/opengraph/twitter/description', 'Stale twitter description.');

$mock_json_ld = [
    'WebPage' => [
        '@type'        => 'WebPage',
        'name'         => 'Stale WebPage Name',
        'description'  => 'Stale WebPage Desc',
        'dateModified' => '2024-01-01T00:00:00+07:00',
    ],
    'richSnippet' => [
        '@type'        => 'Article',
        'headline'     => 'Stale Article Headline',
        'description'  => 'Stale Article Description',
        'dateModified' => '2024-01-01T00:00:00+07:00',
    ],
];
$filtered_schema = apply_filters('rank_math/json_ld', $mock_json_ld, null);

echo json_encode([
    'doc_title' => $filtered_doc_title,
    'rm_title'  => $filtered_rm_title,
    'og_title'  => $filtered_og_title,
    'rm_desc'   => $filtered_rm_desc,
    'tw_desc'   => $filtered_tw_desc,
    'schema'    => $filtered_schema,
]);
"""
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        mang_den_record = next(
            r for r in self.routes if r.get('path') == 'destinations/mang-den-travel-guide'
        )
        expected_title = mang_den_record['title']
        expected_desc = mang_den_record['description']

        self.assertEqual(out['doc_title'], expected_title)
        self.assertEqual(out['rm_title'], expected_title)
        self.assertEqual(out['og_title'], expected_title)
        self.assertLessEqual(len(out['rm_title']), 60)

        self.assertEqual(out['rm_desc'], expected_desc)
        self.assertEqual(out['tw_desc'], expected_desc)
        self.assertTrue(147 <= len(out['rm_desc']) <= 157)

        self.assertEqual(out['schema']['richSnippet']['headline'], expected_title)
        self.assertEqual(out['schema']['richSnippet']['description'], expected_desc)
        self.assertEqual(out['schema']['WebPage']['name'], expected_title)
        self.assertEqual(out['schema']['WebPage']['description'], expected_desc)
        self.assertEqual(out['schema']['WebPage']['dateModified'], '2026-09-28T12:00:00+07:00')

    def test_sitemap_lastmod_sync_with_registry(self):
        """XML Sitemap entries (Rank Math and WP Core) must synchronize lastmod with canonical registry last_reviewed."""
        php_code = r"""
$rm_entry = [
    'loc' => 'https://vietnamguide.net/destinations/mang-den-travel-guide/',
    'mod' => '2025-01-01T00:00:00+00:00',
];
$filtered_rm = apply_filters('rank_math/sitemap/entry', $rm_entry, 'post', null);

$p = new WP_Post();
$p->ID = 777;
$p->post_name = 'destinations/mang-den-travel-guide';
$GLOBALS['post'] = $p;
$wp_entry = [
    'loc'     => 'https://vietnamguide.net/destinations/mang-den-travel-guide/',
    'lastmod' => '2025-01-01T00:00:00+00:00',
];
$filtered_wp = apply_filters('wp_sitemaps_posts_entry', $wp_entry, $p, 'page');

echo json_encode([
    'rm_mod'     => $filtered_rm['mod'],
    'wp_lastmod' => $filtered_wp['lastmod'],
]);
"""
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)
        self.assertEqual(out['rm_mod'], '2026-09-28T12:00:00+07:00')
        self.assertEqual(out['wp_lastmod'], '2026-09-28T12:00:00+07:00')

    def test_travel_clusters_registry_all_12_clusters(self):
        """All 12 travel clusters in vg_get_travel_clusters_registry() must be defined with valid schemas, geo coordinates, and journey links."""
        php_code = """
        $clusters = vg_get_travel_clusters_registry();
        echo json_encode([
            'keys'     => array_keys($clusters),
            'clusters' => $clusters,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        data = json.loads(res.stdout)
        keys = data['keys']
        clusters = data['clusters']

        expected_12 = [
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
        ]
        self.assertEqual(len(keys), 12, "vg_get_travel_clusters_registry must contain exactly 12 clusters")
        self.assertEqual(keys, expected_12, "Cluster keys must match canonical 12 travel clusters list")

        # Validate 4 new clusters
        new_4 = ['central_highlands', 'phong_nha_caves', 'mekong_delta', 'far_north_frontier']
        for cid in new_4:
            self.assertIn(cid, clusters, f"Cluster {cid} must exist in registry")
            c = clusters[cid]
            self.assertEqual(c['id'], cid)
            self.assertIn('destination_schema', c)
            self.assertIn('trip_schema', c)
            self.assertIn('action_schema', c)
            self.assertIn('journey_links', c)

            # Check geo coordinates in Vietnam bounding box
            geo = c['destination_schema']['geo']
            lat = float(geo['latitude'])
            lon = float(geo['longitude'])
            self.assertTrue(8.0 <= lat <= 24.0, f"{cid} latitude {lat} outside Vietnam bounds [8.0, 24.0]")
            self.assertTrue(102.0 <= lon <= 110.0, f"{cid} longitude {lon} outside Vietnam bounds [102.0, 110.0]")

            # Check journey links
            self.assertGreaterEqual(len(c['journey_links']), 3, f"{cid} must define at least 3 journey links")
            for jl in c['journey_links']:
                self.assertIn('name', jl)
                self.assertIn('url', jl)
                self.assertIn('transit', jl)
                self.assertIn('badge', jl)
                self.assertIn('desc', jl)

        # Validate all 12 clusters
        for cid, c in clusters.items():
            with self.subTest(cluster=cid):
                dest = c['destination_schema']
                self.assertTrue(len(dest.get('name', '')) > 0)
                self.assertTrue(len(dest.get('description', '')) > 0)
                self.assertTrue(dest.get('wikidata', '').startswith('https://www.wikidata.org/wiki/'))
                self.assertTrue(dest.get('wikipedia', '').startswith('https://en.wikipedia.org/wiki/'))
                self.assertGreaterEqual(len(dest.get('attractions', [])), 3)

                trip = c['trip_schema']
                self.assertTrue(len(trip.get('name', '')) > 0)
                self.assertGreaterEqual(len(trip.get('stops', [])), 3)

                action = c['action_schema']
                self.assertTrue(len(action.get('name', '')) > 0)
                self.assertTrue(len(action.get('method', '')) > 0)

    def test_travel_cluster_path_resolution_regional_mapping(self):
        """vg_find_travel_cluster_by_path() must accurately resolve regional routes to their regional clusters rather than national fallback."""
        test_routes = [
            # 4 New clusters
            ('destinations/da-lat-travel-guide', 'central_highlands'),
            ('destinations/mang-den-travel-guide', 'central_highlands'),
            ('destinations/where-to-stay-in-buon-ma-thuot', 'central_highlands'),
            ('destinations/where-to-stay-in-pleiku', 'central_highlands'),
            ('destinations/where-to-stay-in-kon-tum', 'central_highlands'),
            ('destinations/where-to-stay-in-bao-loc', 'central_highlands'),
            ('destinations/phong-nha-travel-guide', 'phong_nha_caves'),
            ('destinations/where-to-stay-in-dong-hoi', 'phong_nha_caves'),
            ('plan/phong-nha-cave-treks', 'phong_nha_caves'),
            ('destinations/mekong-delta-travel-guide', 'mekong_delta'),
            ('destinations/where-to-stay-in-can-tho', 'mekong_delta'),
            ('destinations/where-to-stay-in-chau-doc', 'mekong_delta'),
            ('destinations/where-to-stay-in-ben-tre', 'mekong_delta'),
            ('destinations/where-to-stay-in-ca-mau', 'mekong_delta'),
            ('destinations/where-to-stay-in-ha-tien', 'mekong_delta'),
            ('plan/vietnam-to-cambodia-boat-guide', 'mekong_delta'),
            ('destinations/cao-bang-travel-guide', 'far_north_frontier'),
            ('destinations/ba-be-lake-travel-guide', 'far_north_frontier'),
            ('destinations/where-to-stay-in-mai-chau', 'far_north_frontier'),
            ('destinations/where-to-stay-in-bac-ha', 'far_north_frontier'),
            ('destinations/where-to-stay-in-dien-bien-phu', 'far_north_frontier'),
            ('itineraries/northeast-vietnam-itinerary', 'far_north_frontier'),
            # Existing regional clusters
            ('destinations/hanoi-travel-guide', 'northern_triangle'),
            ('destinations/hanoi-old-quarter-walking-tour', 'northern_triangle'),
            ('destinations/ha-long-bay-travel-guide', 'ha_long_bay'),
            ('destinations/cat-ba-island-guide', 'ha_long_bay'),
            ('destinations/ninh-binh-travel-guide', 'ninh_binh'),
            ('destinations/trang-an-boat-tour-guide', 'ninh_binh'),
            ('destinations/da-nang-travel-guide', 'central_heritage'),
            ('destinations/hoi-an-ancient-town-guide', 'central_heritage'),
            ('destinations/hue-imperial-city-guide', 'central_heritage'),
            ('destinations/ho-chi-minh-city-travel-guide', 'southern_delta'),
            ('destinations/saigon-street-food-guide', 'southern_delta'),
            ('destinations/cu-chi-tunnels-guide', 'southern_delta'),
            ('destinations/sapa-travel-guide', 'northern_highlands'),
            ('destinations/ha-giang-loop-planning-guide', 'northern_highlands'),
            ('destinations/mu-cang-chai-travel-guide', 'northern_highlands'),
            ('destinations/pu-luong-travel-guide', 'northern_highlands'),
            ('destinations/phu-quoc-travel-guide', 'coastal_islands'),
            ('destinations/con-dao-travel-guide', 'coastal_islands'),
            ('destinations/nha-trang-travel-guide', 'coastal_islands'),
            ('destinations/quy-nhon-travel-guide', 'coastal_islands'),
            ('destinations/mui-ne-travel-guide', 'coastal_islands'),
            # Valid national circuit fallbacks
            ('plan/vietnam-travel-guide', 'national_circuit'),
            ('plan/vietnam-evisa-guide', 'national_circuit'),
            ('plan/sim-esim-vietnam', 'national_circuit'),
            ('itineraries/10-days-in-vietnam', 'national_circuit'),
        ]

        php_code = """
        $tests = """ + json.dumps([r[0] for r in test_routes]) + """;
        $results = [];
        foreach ($tests as $t) {
            $cl = vg_find_travel_cluster_by_path($t);
            $results[$t] = $cl['id'] ?? 'unknown';
        }
        echo json_encode($results);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        mapped = json.loads(res.stdout)

        for path, expected_cluster in test_routes:
            with self.subTest(route=path):
                self.assertEqual(
                    mapped.get(path),
                    expected_cluster,
                    f"Route {path} should map to {expected_cluster}, got {mapped.get(path)}",
                )

    def test_schema_org_json_ld_tourist_destination_and_trip_entities(self):
        """Schema.org JSON-LD output must include TouristDestination, TouristTrip, TravelAction, containedInPlace, geo, includesAttraction, and ImageObject with width >= 1200px."""
        sample_routes = [
            'destinations/da-lat-travel-guide',
            'destinations/phong-nha-travel-guide',
            'destinations/mekong-delta-travel-guide',
            'destinations/cao-bang-travel-guide',
            'destinations/hanoi-travel-guide',
        ]
        for route_path in sample_routes:
            with self.subTest(route=route_path):
                php_code = f"""
                $route = '/{route_path}/';
                $post = new WP_Post();
                $post->post_name = '{route_path}';
                $post->post_title = 'Travel Guide';
                $GLOBALS['post'] = $post;
                $_SERVER['REQUEST_URI'] = $route;

                $initial_data = [
                    '@context' => 'https://schema.org',
                    '@graph'   => [
                        [
                            '@type' => 'Article',
                            '@id'   => 'https://vietnamguide.net' . $route . '#article',
                            'headline' => 'Sample Article',
                        ],
                        [
                            '@type' => 'WebPage',
                            '@id'   => 'https://vietnamguide.net' . $route . '#webpage',
                            'name'  => 'Sample Page',
                        ],
                    ],
                ];

                $enriched = apply_filters('rank_math/json_ld', $initial_data, null);
                echo json_encode($enriched);
                """
                res = _run_php(php_code)
                self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
                data = json.loads(res.stdout)
                graph = data.get('@graph', [])

                # 1. TouristDestination
                dest_nodes = [n for n in graph if n.get('@type') == 'TouristDestination']
                self.assertTrue(len(dest_nodes) >= 1, "Must contain TouristDestination node")
                dest = dest_nodes[0]
                self.assertIn('containedInPlace', dest)
                self.assertIn('geo', dest)
                self.assertEqual(dest['geo'].get('@type'), 'GeoCoordinates')
                lat = float(dest['geo']['latitude'])
                lon = float(dest['geo']['longitude'])
                self.assertTrue(8.0 <= lat <= 24.0, f"Latitude {lat} out of bounds")
                self.assertTrue(102.0 <= lon <= 110.0, f"Longitude {lon} out of bounds")

                self.assertIn('includesAttraction', dest)
                self.assertGreaterEqual(len(dest['includesAttraction']), 1)
                for attr in dest['includesAttraction']:
                    self.assertEqual(attr.get('@type'), 'TouristAttraction')
                    self.assertIsNotNone(attr.get('name'))

                # 2. TouristTrip
                trip_nodes = [n for n in graph if n.get('@type') == 'TouristTrip']
                self.assertTrue(len(trip_nodes) >= 1, "Must contain TouristTrip node")
                trip = trip_nodes[0]
                self.assertIn('itinerary', trip)

                # 3. TravelAction
                action_nodes = [n for n in graph if n.get('@type') == 'TravelAction']
                self.assertTrue(len(action_nodes) >= 1, "Must contain TravelAction node")
                action = action_nodes[0]
                self.assertIn('fromLocation', action)
                self.assertIn('toLocation', action)

                # 4. Article ImageObject >= 1200px
                art_nodes = [n for n in graph if n.get('@type') == 'Article']
                self.assertTrue(len(art_nodes) >= 1)
                article = art_nodes[0]
                img = article.get('image')
                self.assertIsNotNone(img)
                img_obj = img if isinstance(img, dict) else img[0]
                self.assertEqual(img_obj.get('@type'), 'ImageObject')
                self.assertGreaterEqual(int(img_obj.get('width', 0)), 1200)

                # 5. Linkage via about property
                self.assertIn('about', article)
                about_list = article['about'] if isinstance(article['about'], list) else [article['about']]
                about_ids = [a.get('@id') for a in about_list if isinstance(a, dict)]
                self.assertIn(dest['@id'], about_ids)

    def test_tri_file_registry_synchronization_and_sha256_parity(self):
        """Registry tri-file parity check across ops/route_registry.json, inc/guide-route-registry.json, and inc/guide-rollout.php."""
        parity_ok = compile_route_registry(ROUTE_REGISTRY_JSON, THEME_REGISTRY_JSON, THEME_ROLLOUT_PHP, check_only=True)
        self.assertTrue(parity_ok, "Tri-file registry must have 100% SHA-256 byte parity")

        audit_res = audit_registry_parity(REPO_ROOT)
        self.assertTrue(audit_res['passed'], f"Parity audit failed: {audit_res.get('errors')}")

    def test_route_registry_empirical_signal_density_threshold(self):
        """Route registry must maintain empirical signal density >= 98% (current 100% verified across 282/282 routes)."""
        rep = audit_route_registry(ROUTE_REGISTRY_JSON)
        self.assertEqual(rep['route_count'], 282, "Must audit exactly 282 routes")
        self.assertGreaterEqual(rep['empirical_coverage_pct'], 98.0, "Empirical density must be >= 98.0%")
        self.assertEqual(rep['empirical_coverage_pct'], 100.0, "Empirical density must be 100.0%")
        self.assertEqual(rep['empirical_counts']['any_empirical_signal'], 282, "All 282 routes must have empirical signals")
        self.assertEqual(len(rep['invalid_date_routes']), 0, "No invalid dates allowed")
        self.assertEqual(len(rep['stale_routes']), 0, "No stale routes allowed")
        self.assertEqual(len(rep['title_violations']), 0, "No title violations allowed")
        self.assertEqual(len(rep['description_violations']), 0, "No description violations allowed")

    def test_cluster_aware_discover_image_object_and_exact_dimensions(self):
        """Discover ImageObject must resolve cluster-specific 1920px assets with exact natural dimensions."""
        expected_cases = [
            ('destinations/sapa-travel-guide', '1920px-Rice_terraces_in_Sapa%2C_Vietnam.jpg', 1920, 1280),
            ('destinations/da-lat-travel-guide', '1920px-Da_Lat_-_Xuan_Huong_Lake.jpg', 1920, 1275),
            ('destinations/phong-nha-travel-guide', '1920px-Son_River-Quang_Binh_province.jpg', 1920, 1354),
            ('destinations/mekong-delta-travel-guide', '1920px-Vietnam%2C_Phong_Dien%2C_Mekong_Delta%2C_River.jpg', 1920, 1280),
            ('destinations/cao-bang-travel-guide', '1920px-TuSan_Canyon.jpg', 1920, 1080),
            ('destinations/phu-quoc-travel-guide', '1920px-Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg', 1920, 1440),
        ]
        for uri_path, expected_file, exp_w, exp_h in expected_cases:
            with self.subTest(uri_path=uri_path):
                php_code = f"""
                require_once '{(REPO_ROOT / "wordpress/wp-content/themes/vietnamguide-premium/inc/image-dimensions.php").as_posix()}';
                $slug = basename('{uri_path}');
                $post = new WP_Post();
                $post->ID = 700;
                $post->post_name = $slug;
                $GLOBALS['post'] = $post;
                $GLOBALS['vg_queried_id'] = 700;
                $GLOBALS['vg_permalinks'][700] = 'https://vietnamguide.net/{uri_path}/';
                $_SERVER['REQUEST_URI'] = '/{uri_path}/';

                $initial_data = [
                    '@context' => 'https://schema.org',
                    '@graph'   => [
                        [
                            '@type' => 'Article',
                            '@id'   => 'https://vietnamguide.net/{uri_path}/#article',
                            'headline' => 'Sample Article',
                        ],
                        [
                            '@type' => 'WebPage',
                            '@id'   => 'https://vietnamguide.net/{uri_path}/#webpage',
                            'name'  => 'Sample Page',
                        ],
                    ],
                ];
                $enriched = apply_filters('rank_math/json_ld', $initial_data, null);
                echo json_encode($enriched);
                """
                res = _run_php(php_code)
                self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
                data = json.loads(res.stdout)
                graph = data.get('@graph', [])
                article = next(n for n in graph if n.get('@type') == 'Article')
                webpage = next(n for n in graph if n.get('@type') == 'WebPage')
                dest = next(n for n in graph if n.get('@type') == 'TouristDestination')

                img = article.get('image', {})
                self.assertTrue(img.get('url', '').endswith(expected_file), f"Expected {expected_file}, got {img.get('url')}")
                self.assertEqual(img.get('width'), exp_w)
                self.assertEqual(img.get('height'), exp_h)
                self.assertTrue(len(img.get('caption', '')) > 10)
                self.assertEqual(webpage.get('primaryImageOfPage', {}).get('@id'), img.get('@id'))
                self.assertEqual(dest.get('image', {}).get('@id'), img.get('@id'))

    def test_expanded_faq_and_howto_schema_coverage_across_regional_hubs(self):
        """Regional cluster hubs and arrival checklists must emit valid FAQPage and HowTo nodes."""
        faq_slugs = [
            'destinations/hanoi-travel-guide',
            'destinations/ho-chi-minh-city-travel-guide',
            'destinations/da-lat-travel-guide',
            'destinations/mang-den-travel-guide',
            'destinations/phong-nha-travel-guide',
            'destinations/mekong-delta-travel-guide',
            'destinations/cao-bang-travel-guide',
            'destinations/sapa-travel-guide',
            'destinations/ha-giang-loop-planning-guide',
            'destinations/ninh-binh-travel-guide',
            'destinations/ha-long-bay-travel-guide',
            'destinations/da-nang-travel-guide',
            'destinations/hoi-an-ancient-town-guide',
            'destinations/hue-imperial-city-guide',
            'destinations/phu-quoc-travel-guide',
            'destinations/con-dao-travel-guide',
        ]
        howto_slugs = [
            'plan/vietnam-evisa',
            'plan/sim-esim-vietnam',
            'plan/vietnam-airport-arrival-checklist',
            'plan/hanoi-airport-to-old-quarter',
            'plan/vietnam-first-trip-planning-checklist',
        ]
        php_code = f"""
        $faq_paths = {json.dumps(faq_slugs)};
        $howto_paths = {json.dumps(howto_slugs)};
        $faq_results = [];
        foreach ($faq_paths as $p) {{
            $node = vg_get_page_faq_schema($p, "https://vietnamguide.net/{{$p}}/");
            $faq_results[$p] = is_array($node) ? count($node['mainEntity'] ?? []) : 0;
        }}
        $howto_results = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_results[$p] = is_array($node) ? count($node['step'] ?? []) : 0;
        }}
        echo json_encode(['faq' => $faq_results, 'howto' => $howto_results]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)
        for p, count in out['faq'].items():
            self.assertGreaterEqual(count, 3, f"FAQPage for {p} must have >= 3 Q&As, got {count}")
        for p, count in out['howto'].items():
            self.assertGreaterEqual(count, 3, f"HowTo for {p} must have >= 3 steps, got {count}")

    def test_aio_llms_txt_documents_all_12_regional_travel_clusters(self):
        """inc/guide-aio.php must document all 12 regional travel clusters in /llms.txt and /llms-full.txt."""
        aio_php = (REPO_ROOT / 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-aio.php').read_text(encoding='utf-8')
        self.assertIn('## Regional Travel Clusters (12 Geographic Corridors)', aio_php)
        self.assertIn('### 1.8 Regional Geographic Profiles (12 Canonical Travel Clusters)', aio_php)
        for cluster_marker in ['Cluster 1 —', 'Cluster 6 —', 'Cluster 7 —', 'Cluster 10 —', 'Cluster 12 —']:
            self.assertIn(cluster_marker, aio_php)

    def test_rank_math_head_json_ld_buffer_restores_native_types_and_unescaped_entities(self):
        """rank_math/head priority 89/91 output buffering must undo Rank Math's post-filter HTML entity escaping and string coercion."""
        php_code = r"""
        add_action('rank_math/head', static function (): void {
            $polluted_payload = [
                '@context' => 'https://schema.org',
                '@graph'   => [
                    [
                        '@type' => 'ImageObject',
                        '@id'   => 'https://vietnamguide.net/destinations/mang-den-travel-guide/#primaryimage',
                        'url'   => 'https://vietnamguide.net/wp-content/uploads/2026/03/mang-den-pine-forest-kon-tum-vietnam.jpg',
                        'width' => '1280',
                        'height' => '853',
                        'caption' => 'Mang Den Travel Guide 2026: Pine Plateau &amp; Waterfalls',
                    ],
                    [
                        '@type' => 'TouristDestination',
                        '@id'   => 'https://vietnamguide.net/destinations/mang-den-travel-guide/#tourist-destination',
                        'name'  => 'Da Lat &amp; Central Highlands',
                        'publicAccess' => '1',
                        'isAccessibleForFree' => '',
                        'geo'   => [
                            '@type'     => 'GeoCoordinates',
                            'latitude'  => '11.9404',
                            'longitude' => '108.4583',
                        ],
                    ],
                    [
                        '@type' => 'TouristTrip',
                        '@id'   => 'https://vietnamguide.net/destinations/mang-den-travel-guide/#tourist-trip',
                        'itinerary' => [
                            '@type'           => 'ItemList',
                            'numberOfItems'   => '4',
                            'itemListElement' => [
                                [
                                    '@type'    => 'ListItem',
                                    'position' => '1',
                                    'item'     => [
                                        '@type' => 'TouristDestination',
                                        'name'  => 'Da Lat &amp; Pine Plateau',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            echo '<script type="application/ld+json" class="rank-math-schema">' . wp_json_encode($polluted_payload) . '</script>';
        }, 90);

        ob_start();
        do_action('rank_math/head');
        $rendered_html = ob_get_clean();

        echo json_encode(['html' => $rendered_html]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        rendered_html = json.loads(res.stdout)['html']

        self.assertNotIn('&amp;', rendered_html, "JSON-LD script block must not contain raw &amp; HTML entities")
        match = re.search(r'<script[^>]*type="application/ld\+json"[^>]*>(.*?)</script>', rendered_html, re.DOTALL)
        self.assertIsNotNone(match, "Rendered rank_math/head output must contain JSON-LD script tag")

        parsed = json.loads(match.group(1))
        graph = parsed['@graph']
        img = graph[0]
        dest = graph[1]
        trip = graph[2]

        self.assertIs(type(img['width']), int)
        self.assertEqual(img['width'], 1280)
        self.assertIs(type(img['height']), int)
        self.assertEqual(img['height'], 853)
        self.assertEqual(img['caption'], 'Mang Den Travel Guide 2026: Pine Plateau & Waterfalls')

        self.assertEqual(dest['name'], 'Da Lat & Central Highlands')
        self.assertIs(dest['publicAccess'], True)
        self.assertIs(dest['isAccessibleForFree'], False)
        self.assertIs(type(dest['geo']['latitude']), float)
        self.assertAlmostEqual(dest['geo']['latitude'], 11.9404)
        self.assertIs(type(dest['geo']['longitude']), float)
        self.assertAlmostEqual(dest['geo']['longitude'], 108.4583)

        self.assertIs(type(trip['itinerary']['numberOfItems']), int)
        self.assertEqual(trip['itinerary']['numberOfItems'], 4)
        self.assertIs(type(trip['itinerary']['itemListElement'][0]['position']), int)
        self.assertEqual(trip['itinerary']['itemListElement'][0]['position'], 1)
        self.assertEqual(trip['itinerary']['itemListElement'][0]['item']['name'], 'Da Lat & Pine Plateau')

    def test_robots_deduplication_when_rank_math_active_and_canonical_ordering(self):
        """When Rank Math is active, wp_head must not emit a duplicate fallback meta robots tag, and rank_math/frontend/robots must enforce canonical directive order."""
        php_code = r"""
        define('RANK_MATH_VERSION', '1.0.230');
        $GLOBALS['vg_is_singular'] = true;
        $GLOBALS['vg_is_404'] = false;
        $GLOBALS['vg_is_search'] = false;

        $rm_robots = apply_filters('rank_math/frontend/robots', [
            'follow' => 'follow',
            'index'  => 'index',
        ]);

        ob_start();
        do_action('wp_head');
        $head_output = ob_get_clean();

        echo json_encode([
            'rm_keys'     => array_keys($rm_robots),
            'rm_values'   => array_values($rm_robots),
            'head_output' => $head_output,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        self.assertEqual(
            out['rm_keys'][:5],
            ['index', 'follow', 'max-snippet', 'max-image-preview', 'max-video-preview'],
            "rank_math/frontend/robots must normalize directive order starting with index, follow",
        )
        self.assertNotIn(
            'name="robots"',
            out['head_output'],
            "wp_head must not emit a duplicate fallback <meta name=\"robots\"> tag when Rank Math is active",
        )

    def test_non_travel_trust_pages_receive_eeat_person_name_and_organization_enrichment(self):
        """Trust and policy pages (/editorial-policy/) must receive full Organization/Person/Article E-E-A-T enrichment and Person.name fallback while omitting TouristDestination/Trip/Action."""
        php_code = r"""
        $_SERVER['REQUEST_URI'] = '/editorial-policy/';
        $post = new WP_Post();
        $post->ID = 901;
        $post->post_name = 'editorial-policy';
        $post->post_title = 'VietnamGuide Editorial Policy & Independent Standards';
        $GLOBALS['post'] = $post;

        $initial_data = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'Organization',
                    '@id'   => 'https://vietnamguide.net/#organization',
                    'name'  => 'VietnamGuide.net',
                ],
                [
                    '@type' => 'WebPage',
                    '@id'   => 'https://vietnamguide.net/editorial-policy/#webpage',
                    'url'   => 'https://vietnamguide.net/editorial-policy/',
                ],
                [
                    '@type' => 'Person',
                    '@id'   => 'https://vietnamguide.net/editorial-policy/#author',
                ],
                [
                    '@type'  => 'Article',
                    '@id'    => 'https://vietnamguide.net/editorial-policy/#richSnippet',
                    'author' => [
                        '@id' => 'https://vietnamguide.net/editorial-policy/#author',
                    ],
                ],
            ],
        ];

        $enriched = apply_filters('rank_math/json_ld', $initial_data, null);
        echo json_encode($enriched);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        data = json.loads(res.stdout)
        graph = data['@graph']
        types = [n.get('@type') for n in graph]

        self.assertNotIn('TouristDestination', types)
        self.assertNotIn('TouristTrip', types)
        self.assertNotIn('TravelAction', types)

        org = next(n for n in graph if n.get('@type') == 'Organization')
        self.assertEqual(org.get('publishingPrinciples'), 'https://vietnamguide.net/editorial-policy/')
        self.assertEqual(org.get('correctionsPolicy'), 'https://vietnamguide.net/source-update-policy/')
        self.assertIn('knowsAbout', org)

        person = next(n for n in graph if n.get('@type') == 'Person')
        self.assertEqual(person.get('name'), 'VietnamGuide editorial team')
        self.assertEqual(person.get('url'), 'https://vietnamguide.net/editorial-policy/#team')
        self.assertIn('worksFor', person)

        article = next(n for n in graph if n.get('@type') == 'Article')
        self.assertEqual(article['author'].get('name'), 'VietnamGuide editorial team')
        self.assertIn('reviewedBy', article)
        self.assertIn('image', article)
        self.assertGreaterEqual(article['image'].get('width', 0), 1200)

    def test_lcp_hero_image_preloading_contract(self):
        """wp_head must output <link rel='preload' as='image'> for singular routes and front page LCP hero."""
        with open(GUIDE_SEO_PHP, 'r', encoding='utf-8') as f:
            seo_src = f.read()

        # Check that front-page preloads home-hero.webp
        self.assertIn("home-hero.webp", seo_src, "guide-seo.php must preload home-hero.webp on front page for CWV LCP optimization")
        self.assertIn('<link rel="preload" as="image" type="image/webp"', seo_src)
        self.assertIn('<link rel="preload" as="image" href="', seo_src)
        self.assertIn('fetchpriority="high"', seo_src)

    def test_curated_faq_schema_has_100_percent_visible_accordion_parity(self):
        """Every route with curated FAQPage JSON-LD schema must render matching visible <details>/<summary> Q&As with zero <h2> tags."""
        php_code = r"""
        $routes_to_check = [
            '/plan/vietnam-evisa/',
            '/destinations/hanoi-travel-guide/',
            '/destinations/ha-long-bay-travel-guide/',
            '/compare/da-nang-vs-hoi-an/',
            '/costs/vietnam-travel-cost/',
        ];
        $results = [];
        foreach ($routes_to_check as $uri) {
            $schema = vg_get_page_faq_schema($uri, 'https://vietnamguide.net' . $uri);
            $html   = vg_render_curated_faq_accordion($uri, '<p>Existing guide body paragraph.</p>');
            $dedup  = vg_render_curated_faq_accordion($uri, $html);
            $questions = [];
            foreach (($schema['mainEntity'] ?? []) as $q_node) {
                $questions[] = $q_node['name'] ?? '';
            }
            $results[$uri] = [
                'question_count' => count($questions),
                'all_visible'    => array_reduce($questions, function($carry, $q) use ($html) {
                    return $carry && (strpos($html, htmlspecialchars($q, ENT_QUOTES, 'UTF-8')) !== false || strpos($html, $q) !== false);
                }, true),
                'has_h2'         => stripos($html, '<h2') !== false,
                'has_details'    => strpos($html, '<details class="vg-faq-item"') !== false,
                'dedup_empty'    => $dedup === '',
            ];
        }
        echo json_encode($results);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)
        for uri, info in out.items():
            self.assertGreaterEqual(info['question_count'], 3, f"Expected >= 3 FAQ items on {uri}")
            self.assertTrue(info['all_visible'], f"All FAQPage schema questions must be visibly rendered on {uri}")
            self.assertFalse(info['has_h2'], f"Visible FAQ accordion on {uri} must not emit <h2> tags")
            self.assertTrue(info['has_details'], f"Visible FAQ accordion on {uri} must use semantic <details>")
            self.assertTrue(info['dedup_empty'], f"Visible FAQ accordion on {uri} must be idempotent when already present")

    def test_wikimedia_discover_images_cryptographic_md5_path_integrity(self):
        """Every Wikimedia Commons URL in guide-seo.php must have a cryptographically valid MD5 directory prefix /<h[0]>/<h[0:2]>/."""
        import hashlib
        from urllib.parse import unquote
        with open(GUIDE_SEO_PHP, 'r', encoding='utf-8') as f:
            seo_src = f.read()
        pattern = r'https://upload\.wikimedia\.org/wikipedia/commons/thumb/([0-9a-f])/([0-9a-f]{2})/([^/\'"\s]+)/'
        matches = re.findall(pattern, seo_src)
        self.assertGreaterEqual(len(matches), 11, "Must find all regional Wikimedia Commons Discover image URLs")
        for d1, d2, encoded_filename in matches:
            decoded_filename = unquote(encoded_filename).replace(' ', '_')
            md5_hex = hashlib.md5(decoded_filename.encode('utf-8')).hexdigest()
            self.assertEqual(
                (d1, d2),
                (md5_hex[0], md5_hex[:2]),
                f"Broken Wikimedia Commons MD5 directory prefix for {decoded_filename}: found /{d1}/{d2}/, expected /{md5_hex[0]}/{md5_hex[:2]}/"
            )

    def test_expanded_curated_faqs_and_9_gateway_webapplication_schema(self):
        """Expanded curated FAQs (Cat Ba, Nha Trang, Quy Nhon, Mui Ne, Hue, Can Tho, Train Travel) and 9-gateway WebApplication schema must emit verified 2026 ground truth."""
        new_faq_routes = [
            'destinations/cat-ba-travel-guide',
            'destinations/nha-trang-travel-guide',
            'destinations/quy-nhon-travel-guide',
            'destinations/mui-ne-travel-guide',
            'destinations/hue-travel-guide',
            'destinations/can-tho-travel-guide',
            'plan/vietnam-train-travel',
        ]
        php_code = f"""
        $routes = {json.dumps(new_faq_routes)};
        $faq_counts = [];
        foreach ($routes as $r) {{
            $items = vg_get_curated_faq_items($r);
            $faq_counts[$r] = count($items);
        }}
        $evisa_app = vg_get_page_webapplication_schema('plan/vietnam-evisa', 'https://vietnamguide.net/plan/vietnam-evisa/');
        $airport_app = vg_get_page_webapplication_schema('plan/vietnam-airport-arrival-checklist', 'https://vietnamguide.net/plan/vietnam-airport-arrival-checklist/');
        echo json_encode([
            'faq_counts'  => $faq_counts,
            'evisa_app'   => $evisa_app,
            'airport_app' => $airport_app,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)
        for route, count in out['faq_counts'].items():
            self.assertGreaterEqual(count, 3, f"Route {route} must have >= 3 curated FAQ items, got {count}")
        self.assertIn('42 international checkpoints', out['evisa_app']['description'])
        self.assertIn('25 countries under Resolutions 44/NQ-CP & 229/NQ-CP', out['evisa_app']['description'])
        self.assertIn('9 gateway airports (HAN, SGN, DAD, CXR, PQC, HUI, DLI, VDH, HPH)', out['airport_app']['description'])

    def test_subdestination_discover_image_resolution_and_stage117_faqs_howtos(self):
        """Sub-destinations must resolve their own 1920px Wikimedia Commons Discover images, and Stage 117 FAQ/HowTo/Wikidata ground truth must be verified."""
        subdest_cases = [
            ('destinations/hue-travel-guide', '1920px-Hue_Vietnam_Citadel-of-Hu%E1%BA%BF-13.jpg', 1920, 1280),
            ('destinations/da-nang-travel-guide', '1920px-My_Khe_Beach_seen_from_the_Son_Tra_Mountain.jpg', 1920, 1440),
            ('destinations/my-son-sanctuary-guide', '1920px-My_Son_Sanctuary_Vietnam_06.jpg', 1920, 1440),
            ('destinations/cat-ba-travel-guide', '1920px-Lan_Ha_Bay-Cat_Ba_Vietnam-Andres_Larin.jpg', 1920, 1435),
            ('destinations/bai-tu-long-bay-guide', '1920px-B%C3%A1i_T%E1%BB%AD_Long_Bay_-_01.jpg', 1920, 1282),
            ('destinations/con-dao-travel-guide', '1920px-Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_%28April_2022%29.jpg', 1920, 1440),
            ('destinations/nha-trang-travel-guide', '1920px-Nha_Trang_Beach_3.jpg', 1920, 1440),
            ('destinations/quy-nhon-travel-guide', '1920px-Ky_Co_beach%2C_Quy_Nhon_city%2C_Binh_Dinh_province%2C_Vietnam.jpg', 1920, 1280),
            ('destinations/mui-ne-travel-guide', '1920px-Vietnam%2C_Mui_Ne_beach%2C_Kitesurfing_on_the_beach.jpg', 1920, 1280),
        ]
        stage117_faqs = [
            'destinations/ba-be-lake-travel-guide',
            'destinations/pu-luong-travel-guide',
            'destinations/mu-cang-chai-travel-guide',
            'destinations/ly-son-travel-guide',
            'destinations/mai-chau-travel-guide',
            'compare/ninh-binh-vs-ha-long-bay',
            'compare/sapa-vs-ha-giang',
            'compare/phu-quoc-vs-con-dao',
        ]
        stage117_howtos = [
            'plan/vietnam-train-travel',
            'plan/vietnam-to-cambodia-boat-guide',
            'destinations/ha-giang-loop-planning-guide',
        ]
        php_code = f"""
        $subdest_paths = {json.dumps([c[0] for c in subdest_cases])};
        $subdest_imgs = [];
        foreach ($subdest_paths as $p) {{
            $subdest_imgs[$p] = vg_resolve_discover_image_object($p);
        }}
        $faq_paths = {json.dumps(stage117_faqs)};
        $faq_counts = [];
        foreach ($faq_paths as $p) {{
            $faq_counts[$p] = count(vg_get_curated_faq_items($p));
        }}
        $howto_paths = {json.dumps(stage117_howtos)};
        $howto_counts = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_counts[$p] = is_array($node) ? count($node['step'] ?? []) : 0;
        }}
        $clusters = vg_get_travel_clusters_registry();
        echo json_encode([
            'subdest_imgs' => $subdest_imgs,
            'faq_counts'   => $faq_counts,
            'howto_counts' => $howto_counts,
            'nh_attr'      => $clusters['northern_highlands']['destination_schema']['attractions'] ?? [],
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        for uri_path, expected_file, exp_w, exp_h in subdest_cases:
            img = out['subdest_imgs'][uri_path]
            self.assertTrue(img['url'].endswith(expected_file), f"Expected {expected_file} for {uri_path}, got {img['url']}")
            self.assertEqual(img['width'], exp_w)
            self.assertEqual(img['height'], exp_h)

        for route, count in out['faq_counts'].items():
            self.assertGreaterEqual(count, 3, f"Route {route} must have >= 3 curated FAQ items, got {count}")

        for route, count in out['howto_counts'].items():
            self.assertGreaterEqual(count, 4, f"Route {route} must have >= 4 HowTo steps, got {count}")

        dong_van = next((a for a in out['nh_attr'] if a.get('name') == 'Dong Van Karst Plateau'), {})
        self.assertEqual(dong_van.get('wikidata'), 'https://www.wikidata.org/wiki/Q10744325')

    def test_stage118_expanded_faqs_howtos_and_toolkit_ground_truth(self):
        """Stage 118: 10 new curated FAQ routes, 3 new HowTo routes, and enriched Itinerary Finder / Packing Checklist 2026 ground truth."""
        stage118_faqs = [
            'itineraries/7-days-in-vietnam',
            'itineraries/10-days-in-vietnam',
            'itineraries/14-days-in-vietnam',
            'itineraries/21-days-in-vietnam',
            'compare/hanoi-vs-ho-chi-minh-city',
            'plan/transport-within-vietnam',
            'plan/vietnam-domestic-flights-guide',
            'destinations/my-son-sanctuary-guide',
            'destinations/cham-islands-travel-guide',
            'destinations/bai-tu-long-bay-guide',
        ]
        stage118_howtos = [
            'plan/ninh-binh-to-ha-long-bay-transfer',
            'plan/hanoi-to-sapa-transport',
            'destinations/hoi-an-ancient-town-guide',
        ]
        php_code = f"""
        $faq_paths = {json.dumps(stage118_faqs)};
        $faq_results = [];
        foreach ($faq_paths as $p) {{
            $items = vg_get_curated_faq_items($p);
            $html  = vg_render_curated_faq_accordion($p, '<p>Lead content.</p>');
            $faq_results[$p] = [
                'count'   => count($items),
                'has_h2'  => stripos($html, '<h2') !== false,
                'visible' => strpos($html, '<details class="vg-faq-item"') !== false,
            ];
        }}
        $howto_paths = {json.dumps(stage118_howtos)};
        $howto_counts = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_counts[$p] = is_array($node) ? count($node['step'] ?? []) : 0;
        }}
        echo json_encode([
            'faq_results'  => $faq_results,
            'howto_counts' => $howto_counts,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        for route, info in out['faq_results'].items():
            self.assertGreaterEqual(info['count'], 3, f"Route {route} must have >= 3 curated FAQ items")
            self.assertFalse(info['has_h2'], f"Route {route} FAQ accordion must have 0 <h2> tags")
            self.assertTrue(info['visible'], f"Route {route} FAQ accordion must render visible <details>")

        for route, count in out['howto_counts'].items():
            self.assertGreaterEqual(count, 4, f"Route {route} must have >= 4 HowTo steps, got {count}")

        itinerary_finder_php = os.path.join(THEME_DIR, 'inc', 'guide-itinerary-finder.php')
        packing_checklist_php = os.path.join(THEME_DIR, 'inc', 'guide-packing-checklist.php')
        with open(itinerary_finder_php, 'r', encoding='utf-8') as f:
            if_src = f.read()
        with open(packing_checklist_php, 'r', encoding='utf-8') as f:
            pc_src = f.read()

        self.assertIn('120,000 VND heritage ticket', if_src)
        self.assertIn('420,000 VND 3-site combo pass', if_src)
        self.assertIn('9 gateway airports (HAN, SGN, DAD, CXR, PQC, HUI, DLI, VDH, HPH)', if_src)
        self.assertIn('Decree 49/2017 &amp; 163/2024', pc_src)
        self.assertIn('9 gateway airports (HAN, SGN, DAD, CXR, PQC, HUI, DLI, VDH, HPH)', pc_src)

    def test_stage119_expanded_faqs_howtos_and_cost_calculator_specs(self):
        """Stage 119: 8 new curated FAQ routes, 2 new HowTo routes, and enriched Cost Calculator 2026 ground truth."""
        stage119_faqs = [
            'plan/money-cash-cards-atms',
            'plan/vietnam-scooter-rental-checklist',
            'plan/safety-scams-vietnam',
            'plan/vietnam-food-safety-street-food-etiquette',
            'plan/tipping-in-vietnam',
            'plan/ha-tien-to-phu-quoc-ferry',
            'plan/ho-chi-minh-city-to-chau-doc-transport',
            'itineraries/ha-long-bay-day-trip-vs-overnight-cruise',
        ]
        stage119_howtos = [
            'plan/vietnam-scooter-rental-checklist',
            'plan/ha-tien-to-phu-quoc-ferry',
        ]
        php_code = f"""
        $faq_paths = {json.dumps(stage119_faqs)};
        $faq_results = [];
        foreach ($faq_paths as $p) {{
            $items = vg_get_curated_faq_items($p);
            $html  = vg_render_curated_faq_accordion($p, '<p>Lead text.</p>');
            $faq_results[$p] = [
                'count'   => count($items),
                'has_h2'  => stripos($html, '<h2') !== false,
                'visible' => strpos($html, '<details class="vg-faq-item"') !== false,
            ];
        }}
        $howto_paths = {json.dumps(stage119_howtos)};
        $howto_counts = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_counts[$p] = is_array($node) ? count($node['step'] ?? []) : 0;
        }}
        echo json_encode([
            'faq_results'  => $faq_results,
            'howto_counts' => $howto_counts,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        for route, info in out['faq_results'].items():
            self.assertGreaterEqual(info['count'], 3, f"Route {route} must have >= 3 curated FAQ items")
            self.assertFalse(info['has_h2'], f"Route {route} FAQ accordion must have 0 <h2> tags")
            self.assertTrue(info['visible'], f"Route {route} FAQ accordion must render visible <details>")

        for route, count in out['howto_counts'].items():
            self.assertGreaterEqual(count, 4, f"Route {route} must have >= 4 HowTo steps, got {count}")

        cost_calc_php = os.path.join(THEME_DIR, 'inc', 'guide-cost-calculator.php')
        with open(cost_calc_php, 'r', encoding='utf-8') as f:
            cc_src = f.read()

        self.assertIn('VPBank charges 0 VND ATM fee', cc_src)
        self.assertIn('Ha Tien–Phu Quoc / Rach Gia–Phu Quoc', cc_src)
        self.assertIn('Grab bays & fares for 9 airports (HAN, SGN, DAD...)', cc_src)

    def test_stage120_central_highlands_and_mekong_faqs_and_howtos(self):
        """Stage 120: 6 new Central Highlands & Mekong Delta FAQ routes, 1 new HowTo route with verified ground-truth data."""
        stage120_faqs = [
            'destinations/where-to-stay-in-buon-ma-thuot',
            'destinations/where-to-stay-in-pleiku',
            'destinations/where-to-stay-in-chau-doc',
            'destinations/where-to-stay-in-ha-tien',
            'plan/can-tho-to-chau-doc-transport',
            'plan/can-tho-to-ha-tien-transport',
        ]
        stage120_howtos = [
            'plan/can-tho-to-chau-doc-transport',
        ]
        php_code = f"""
        $faq_paths = {json.dumps(stage120_faqs)};
        $faq_results = [];
        foreach ($faq_paths as $p) {{
            $items = vg_get_curated_faq_items($p);
            $html  = vg_render_curated_faq_accordion($p, '<p>Lead text.</p>');
            $faq_results[$p] = [
                'count'   => count($items),
                'has_h2'  => stripos($html, '<h2') !== false,
                'visible' => strpos($html, '<details class="vg-faq-item"') !== false,
                'items'   => $items,
            ];
        }}
        $howto_paths = {json.dumps(stage120_howtos)};
        $howto_data = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_data[$p] = [
                'step_count' => is_array($node) ? count($node['step'] ?? []) : 0,
                'name'       => $node['name'] ?? '',
                'steps'      => $node['step'] ?? [],
            ];
        }}
        echo json_encode([
            'faq_results' => $faq_results,
            'howto_data'  => $howto_data,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        for route, info in out['faq_results'].items():
            self.assertGreaterEqual(info['count'], 3, f"Route {route} must have >= 3 curated FAQ items")
            self.assertFalse(info['has_h2'], f"Route {route} FAQ accordion must have 0 <h2> tags")
            self.assertTrue(info['visible'], f"Route {route} FAQ accordion must render visible <details>")

        # Verify ground-truth empirical metrics in FAQs
        bmt_faqs = str(out['faq_results']['destinations/where-to-stay-in-buon-ma-thuot']['items'])
        self.assertIn('Dray Nur Waterfall', bmt_faqs)
        self.assertIn('30,000 to 40,000 VND', bmt_faqs)
        self.assertIn('Srepok River', bmt_faqs)
        self.assertIn('QL26', bmt_faqs)

        pleiku_faqs = str(out['faq_results']['destinations/where-to-stay-in-pleiku']['items'])
        self.assertIn('Bien Ho', pleiku_faqs)
        self.assertIn('free admission', pleiku_faqs)
        self.assertIn('10,000 VND', pleiku_faqs)
        self.assertIn('Chu Dang Ya', pleiku_faqs)
        self.assertIn('Kon Tum', pleiku_faqs)

        chau_doc_faqs = str(out['faq_results']['destinations/where-to-stay-in-chau-doc']['items'])
        self.assertIn('Tra Su Cajuput Forest', chau_doc_faqs)
        self.assertIn('120,000 VND', chau_doc_faqs)
        self.assertIn('80,000 VND', chau_doc_faqs)
        self.assertIn('70,000 VND', chau_doc_faqs)

        ha_tien_faqs = str(out['faq_results']['destinations/where-to-stay-in-ha-tien']['items'])
        self.assertIn('Thach Dong Cave Temple', ha_tien_faqs)
        self.assertIn('20,000 VND', ha_tien_faqs)
        self.assertIn('Mui Nai Beach', ha_tien_faqs)
        self.assertIn('Prek Chak', ha_tien_faqs)

        cantho_chaudoc_faqs = str(out['faq_results']['plan/can-tho-to-chau-doc-transport']['items'])
        self.assertIn('FUTA Bus Lines', cantho_chaudoc_faqs)
        self.assertIn('150,000 to 220,000 VND', cantho_chaudoc_faqs)
        self.assertIn('QL91', cantho_chaudoc_faqs)

        cantho_hatien_faqs = str(out['faq_results']['plan/can-tho-to-ha-tien-transport']['items'])
        self.assertIn('FUTA Bus Lines', cantho_hatien_faqs)
        self.assertIn('220,000 to 240,000 VND', cantho_hatien_faqs)
        self.assertIn('190 kilometers', cantho_hatien_faqs)

        # Verify HowTo schema
        howto = out['howto_data']['plan/can-tho-to-chau-doc-transport']
        self.assertGreaterEqual(howto['step_count'], 4)
        self.assertIn('Tra Su Cajuput Forest', howto['name'])
        steps_text = ' '.join(s.get('text', '') for s in howto['steps'])
        self.assertIn('Ben xe Trung tam Can Tho', steps_text)
        self.assertIn('QL91', steps_text)
        self.assertIn('Tra Su Cajuput Forest', steps_text)
        self.assertIn('120,000 VND', steps_text)

    def test_stage121_central_coast_and_caves_faqs_and_howtos(self):
        """Stage 121: 7 new South Central Coast & Cave Kingdom FAQ routes, 1 new HowTo route with verified ground-truth data."""
        stage121_faqs = [
            'destinations/where-to-stay-in-quy-nhon',
            'destinations/where-to-stay-in-nha-trang',
            'destinations/where-to-stay-in-mui-ne',
            'destinations/where-to-stay-in-dong-hoi',
            'plan/buon-ma-thuot-to-nha-trang-transport',
            'plan/pleiku-to-quy-nhon-transport',
            'plan/nha-trang-to-quy-nhon-transport',
        ]
        stage121_howtos = [
            'plan/nha-trang-to-quy-nhon-transport',
        ]
        php_code = f"""
        $faq_paths = {json.dumps(stage121_faqs)};
        $faq_results = [];
        foreach ($faq_paths as $p) {{
            $items = vg_get_curated_faq_items($p);
            $html  = vg_render_curated_faq_accordion($p, '<p>Lead text.</p>');
            $faq_results[$p] = [
                'count'   => count($items),
                'has_h2'  => stripos($html, '<h2') !== false,
                'visible' => strpos($html, '<details class="vg-faq-item"') !== false,
                'items'   => $items,
            ];
        }}
        $howto_paths = {json.dumps(stage121_howtos)};
        $howto_data = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_data[$p] = [
                'step_count' => is_array($node) ? count($node['step'] ?? []) : 0,
                'name'       => $node['name'] ?? '',
                'steps'      => $node['step'] ?? [],
            ];
        }}
        echo json_encode([
            'faq_results' => $faq_results,
            'howto_data'  => $howto_data,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        for route, info in out['faq_results'].items():
            self.assertGreaterEqual(info['count'], 3, f"Route {route} must have >= 3 curated FAQ items")
            self.assertFalse(info['has_h2'], f"Route {route} FAQ accordion must have 0 <h2> tags")
            self.assertTrue(info['visible'], f"Route {route} FAQ accordion must render visible <details>")

        # Verify ground-truth empirical metrics in FAQs
        quynhon_faqs = str(out['faq_results']['destinations/where-to-stay-in-quy-nhon']['items'])
        self.assertIn('Eo Gio', quynhon_faqs)
        self.assertIn('25,000 VND', quynhon_faqs)
        self.assertIn('Ky Co Beach', quynhon_faqs)
        self.assertIn('Phu Cat Airport', quynhon_faqs)
        self.assertIn('50,000 VND', quynhon_faqs)

        nhatrang_faqs = str(out['faq_results']['destinations/where-to-stay-in-nha-trang']['items'])
        self.assertIn('VinWonders Nha Trang', nhatrang_faqs)
        self.assertIn('950,000 to 1,050,000 VND', nhatrang_faqs)
        self.assertIn('Ponagar Cham Towers', nhatrang_faqs)
        self.assertIn('30,000 VND', nhatrang_faqs)
        self.assertIn('Bus No. 18', nhatrang_faqs)

        muine_faqs = str(out['faq_results']['destinations/where-to-stay-in-mui-ne']['items'])
        self.assertIn('White Sand Dunes', muine_faqs)
        self.assertIn('Fairy Stream', muine_faqs)
        self.assertIn('15,000 VND', muine_faqs)
        self.assertIn('SPT2', muine_faqs)
        self.assertIn('170,000 to 230,000 VND', muine_faqs)

        donghoi_faqs = str(out['faq_results']['destinations/where-to-stay-in-dong-hoi']['items'])
        self.assertIn('Phong Nha Cave', donghoi_faqs)
        self.assertIn('150,000 VND', donghoi_faqs)
        self.assertIn('700,000 VND', donghoi_faqs)
        self.assertIn('Paradise Cave', donghoi_faqs)
        self.assertIn('270,000 VND', donghoi_faqs)
        self.assertIn('Bus B4', donghoi_faqs)

        bmt_nt_faqs = str(out['faq_results']['plan/buon-ma-thuot-to-nha-trang-transport']['items'])
        self.assertIn('QL26', bmt_nt_faqs)
        self.assertIn('185-kilometer', bmt_nt_faqs)
        self.assertIn('Phuong Hoang Mountain Pass', bmt_nt_faqs)
        self.assertIn('180,000 to 250,000 VND', bmt_nt_faqs)

        pleiku_qn_faqs = str(out['faq_results']['plan/pleiku-to-quy-nhon-transport']['items'])
        self.assertIn('QL19', pleiku_qn_faqs)
        self.assertIn('165-kilometer', pleiku_qn_faqs)
        self.assertIn('Mang Yang Pass', pleiku_qn_faqs)
        self.assertIn('An Khe Pass', pleiku_qn_faqs)
        self.assertIn('140,000 to 180,000 VND', pleiku_qn_faqs)

        nt_qn_faqs = str(out['faq_results']['plan/nha-trang-to-quy-nhon-transport']['items'])
        self.assertIn('SE22', nt_qn_faqs)
        self.assertIn('Dieu Tri Station', nt_qn_faqs)
        self.assertIn('215-kilometer', nt_qn_faqs)
        self.assertIn('150,000 to 230,000 VND', nt_qn_faqs)

        # Verify HowTo schema
        howto = out['howto_data']['plan/nha-trang-to-quy-nhon-transport']
        self.assertGreaterEqual(howto['step_count'], 4)
        self.assertIn('Nha Trang to Quy Nhon', howto['name'])
        steps_text = ' '.join(s.get('text', '') for s in howto['steps'])
        self.assertIn('SE22', steps_text)
        self.assertIn('Nha Trang Station', steps_text)
        self.assertIn('215 kilometers', steps_text.lower())
        self.assertIn('Dieu Tri', steps_text)

    def test_stage122_northern_mountains_faqs_and_howtos(self):
        """Stage 122: 6 new Northern Mountains & Far North Frontier FAQ routes, 1 new HowTo route with verified ground-truth data."""
        stage122_faqs = [
            'destinations/where-to-stay-in-sapa',
            'destinations/where-to-stay-in-ha-giang',
            'destinations/where-to-stay-in-cao-bang',
            'plan/hanoi-to-ha-giang-transport',
            'plan/ha-giang-to-dong-van-transport',
            'compare/sapa-vs-ha-giang',
        ]
        stage122_howtos = [
            'plan/hanoi-to-ha-giang-transport',
        ]
        php_code = f"""
        $faq_paths = {json.dumps(stage122_faqs)};
        $faq_results = [];
        foreach ($faq_paths as $p) {{
            $items = vg_get_curated_faq_items($p);
            $html  = vg_render_curated_faq_accordion($p, '<p>Lead text.</p>');
            $faq_results[$p] = [
                'count'   => count($items),
                'has_h2'  => stripos($html, '<h2') !== false,
                'visible' => strpos($html, '<details class="vg-faq-item"') !== false,
                'items'   => $items,
            ];
        }}
        $howto_paths = {json.dumps(stage122_howtos)};
        $howto_data = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_data[$p] = [
                'step_count' => is_array($node) ? count($node['step'] ?? []) : 0,
                'name'       => $node['name'] ?? '',
                'steps'      => $node['step'] ?? [],
            ];
        }}
        echo json_encode([
            'faq_results' => $faq_results,
            'howto_data'  => $howto_data,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        for route, info in out['faq_results'].items():
            self.assertGreaterEqual(info['count'], 3, f"Route {route} must have >= 3 curated FAQ items")
            self.assertFalse(info['has_h2'], f"Route {route} FAQ accordion must have 0 <h2> tags")
            self.assertTrue(info['visible'], f"Route {route} FAQ accordion must render visible <details>")

        # Verify ground-truth empirical metrics in FAQs
        sapa_faqs = str(out['faq_results']['destinations/where-to-stay-in-sapa']['items'])
        self.assertIn('Sun World Fansipan Legend', sapa_faqs)
        self.assertIn('850,000 VND', sapa_faqs)
        self.assertIn('Cat Cat Village', sapa_faqs)
        self.assertIn('150,000 VND', sapa_faqs)
        self.assertIn('Muong Hoa', sapa_faqs)

        hagiang_faqs = str(out['faq_results']['destinations/where-to-stay-in-ha-giang']['items'])
        self.assertIn('Border Area Entry Permit', hagiang_faqs)
        self.assertIn('230,000 VND', hagiang_faqs)
        self.assertIn('Lung Cu Flag Tower', hagiang_faqs)
        self.assertIn('Nho Que River', hagiang_faqs)
        self.assertIn('120,000 to 150,000 VND', hagiang_faqs)

        caobang_faqs = str(out['faq_results']['destinations/where-to-stay-in-cao-bang']['items'])
        self.assertIn('Ban Gioc Waterfall', caobang_faqs)
        self.assertIn('45,000 VND', caobang_faqs)
        self.assertIn('Quay Son River', caobang_faqs)
        self.assertIn('50,000 VND', caobang_faqs)
        self.assertIn('Nguom Ngao Cave', caobang_faqs)

        hn_hg_faqs = str(out['faq_results']['plan/hanoi-to-ha-giang-transport']['items'])
        self.assertIn('Bang Phan', hn_hg_faqs)
        self.assertIn('CT05', hn_hg_faqs)
        self.assertIn('300,000 to 350,000 VND', hn_hg_faqs)
        self.assertIn('My Dinh Bus Station', hn_hg_faqs)
        self.assertIn('Noi Bai International Airport', hn_hg_faqs)

        hg_dv_faqs = str(out['faq_results']['plan/ha-giang-to-dong-van-transport']['items'])
        self.assertIn('QL4C', hg_dv_faqs)
        self.assertIn('135 to 145 kilometers', hg_dv_faqs)
        self.assertIn('Bac Sum', hg_dv_faqs)
        self.assertIn('Tham Ma', hg_dv_faqs)
        self.assertIn('100,000 to 120,000 VND', hg_dv_faqs)

        sapa_hg_faqs = str(out['faq_results']['compare/sapa-vs-ha-giang']['items'])
        self.assertIn('240-kilometer', sapa_hg_faqs)
        self.assertIn('QL279', sapa_hg_faqs)
        self.assertIn('250,000 to 300,000 VND', sapa_hg_faqs)
        self.assertIn('Dong Van Karst Plateau', sapa_hg_faqs)

        # Verify HowTo schema
        howto = out['howto_data']['plan/hanoi-to-ha-giang-transport']
        self.assertGreaterEqual(howto['step_count'], 4)
        self.assertIn('Sleeper Bus from Hanoi to Ha Giang', howto['name'])
        steps_text = ' '.join(s.get('text', '') for s in howto['steps'])
        self.assertIn('My Dinh Bus Station', steps_text)
        self.assertIn('280 kilometers', steps_text.lower())
        self.assertIn('Bang Phan', steps_text)
        self.assertIn('Ha Giang City', steps_text)

    def test_stage123_central_heritage_cuchi_islands_faqs_and_howtos(self):
        """Stage 123: 7 new Central Heritage, Cu Chi, and Island comparison FAQ routes, 2 new HowTo routes with verified ground-truth data."""
        stage123_faqs = [
            'compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh',
            'compare/con-dao-vs-phu-quoc',
            'compare/hoi-an-vs-hue',
            'destinations/ba-na-hills-golden-bridge-guide',
            'plan/da-nang-airport-to-hoi-an',
            'itineraries/da-nang-to-hue-train-vs-car',
            'compare/cu-chi-tunnels-vs-mekong-delta-day-trip',
        ]
        stage123_howtos = [
            'plan/da-nang-airport-to-hoi-an',
            'itineraries/da-nang-to-hue-train-vs-car',
        ]
        php_code = f"""
        $faq_paths = {json.dumps(stage123_faqs)};
        $faq_results = [];
        foreach ($faq_paths as $p) {{
            $items = vg_get_curated_faq_items($p);
            $html  = vg_render_curated_faq_accordion($p, '<p>Lead text.</p>');
            $faq_results[$p] = [
                'count'   => count($items),
                'has_h2'  => stripos($html, '<h2') !== false,
                'visible' => strpos($html, '<details class="vg-faq-item"') !== false,
                'items'   => $items,
            ];
        }}
        $howto_paths = {json.dumps(stage123_howtos)};
        $howto_data = [];
        foreach ($howto_paths as $p) {{
            $node = vg_get_page_howto_schema($p, "https://vietnamguide.net/{{$p}}/");
            $howto_data[$p] = [
                'step_count' => is_array($node) ? count($node['step'] ?? []) : 0,
                'name'       => $node['name'] ?? '',
                'steps'      => $node['step'] ?? [],
            ];
        }}
        echo json_encode([
            'faq_results' => $faq_results,
            'howto_data'  => $howto_data,
        ]);
        """
        res = _run_php(php_code)
        self.assertEqual(res.returncode, 0, f"PHP error:\n{res.stderr}")
        out = json.loads(res.stdout)

        for route, info in out['faq_results'].items():
            self.assertGreaterEqual(info['count'], 3, f"Route {route} must have >= 3 curated FAQ items")
            self.assertFalse(info['has_h2'], f"Route {route} FAQ accordion must have 0 <h2> tags")
            self.assertTrue(info['visible'], f"Route {route} FAQ accordion must render visible <details>")

        # Verify ground-truth empirical metrics in FAQs
        cuchi_bd_faqs = str(out['faq_results']['compare/cu-chi-tunnels-ben-duoc-vs-ben-dinh']['items'])
        self.assertIn('125,000 VND', cuchi_bd_faqs)
        self.assertIn('Bus No. 13', cuchi_bd_faqs)
        self.assertIn('Bus No. 79', cuchi_bd_faqs)
        self.assertIn('14,000 VND', cuchi_bd_faqs)

        condao_pq_faqs = str(out['faq_results']['compare/con-dao-vs-phu-quoc']['items'])
        self.assertIn('574-square-kilometer', condao_pq_faqs)
        self.assertIn('30-day visa', condao_pq_faqs)
        self.assertIn('76-square-kilometer', condao_pq_faqs)
        self.assertIn('Con Dao National Park', condao_pq_faqs)

        hoian_hue_faqs = str(out['faq_results']['compare/hoi-an-vs-hue']['items'])
        self.assertIn('130 kilometers', hoian_hue_faqs)
        self.assertIn('HD1/HD3', hoian_hue_faqs)
        self.assertIn('Khai Dinh', hoian_hue_faqs)
        self.assertIn('Cao Lau', hoian_hue_faqs)

        bana_faqs = str(out['faq_results']['destinations/ba-na-hills-golden-bridge-guide']['items'])
        self.assertIn('1,000,000 VND', bana_faqs)
        self.assertIn('800,000 VND', bana_faqs)
        self.assertIn('Golden Bridge', bana_faqs)
        self.assertIn('Cau Vang', bana_faqs)

        dna_ha_faqs = str(out['faq_results']['plan/da-nang-airport-to-hoi-an']['items'])
        self.assertIn('300,000 to 350,000 VND', dna_ha_faqs)
        self.assertIn('100,000 to 120,000 VND', dna_ha_faqs)
        self.assertIn('Vo Nguyen Giap', dna_ha_faqs)

        dnh_hue_faqs = str(out['faq_results']['itineraries/da-nang-to-hue-train-vs-car']['items'])
        self.assertIn('HD1/HD2/HD3/HD4', dnh_hue_faqs)
        self.assertIn('Hai Van Pass', dnh_hue_faqs)
        self.assertIn('Lang Co Bay', dnh_hue_faqs)
        self.assertIn('110,000 to 180,000 VND', dnh_hue_faqs)

        cuchi_mekong_faqs = str(out['faq_results']['compare/cu-chi-tunnels-vs-mekong-delta-day-trip']['items'])
        self.assertIn('125,000 VND', cuchi_mekong_faqs)
        self.assertIn('My Tho', cuchi_mekong_faqs)
        self.assertIn('Ben Tre', cuchi_mekong_faqs)

        # Verify HowTo schemas
        howto_airport = out['howto_data']['plan/da-nang-airport-to-hoi-an']
        self.assertGreaterEqual(howto_airport['step_count'], 4)
        self.assertIn('Da Nang Airport to Hoi An', howto_airport['name'])
        steps_text_airport = ' '.join(s.get('text', '') for s in howto_airport['steps'])
        self.assertIn('Terminal T1', steps_text_airport)
        self.assertIn('Vo Nguyen Giap', steps_text_airport)

        howto_train = out['howto_data']['itineraries/da-nang-to-hue-train-vs-car']
        self.assertGreaterEqual(howto_train['step_count'], 4)
        self.assertIn('Hai Van Pass between Da Nang and Hue', howto_train['name'])
        steps_text_train = ' '.join(s.get('text', '') for s in howto_train['steps'])
        self.assertIn('HD1/HD3', steps_text_train)
        self.assertIn('Lang Co', steps_text_train)


if __name__ == '__main__':
    unittest.main()

