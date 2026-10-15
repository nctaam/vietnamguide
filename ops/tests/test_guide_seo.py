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
import re
import subprocess
import tempfile
import unittest
from pathlib import Path

REPO_ROOT = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..')))
THEME_INC = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'inc'
THEME_DIR = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium'
MU_PLUGIN = REPO_ROOT / 'wordpress' / 'wp-content' / 'mu-plugins' / 'vietnamguide-core.php'
ROUTE_REGISTRY_JSON = REPO_ROOT / 'ops' / 'route_registry.json'
THEME_REGISTRY_JSON = THEME_INC / 'guide-route-registry.json'
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


if __name__ == '__main__':
    unittest.main()

