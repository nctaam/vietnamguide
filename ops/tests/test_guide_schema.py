# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Schema.org JSON-LD Generation & CWV Image Dimensions.

Validates Schema.org JSON-LD graph generation across all 4 canonical guide classifications:
1. Destination Guides (TouristDestination, GeoCoordinates, attractions, containedInPlace)
2. Route Comparisons (Article, FAQPage, multi-destination entities, speakable, breadcrumb)
3. Itineraries (TouristTrip, multi-stop day/stop ItemList, TravelAction)
4. Practical Essentials (FAQPage, HowTo, WebApplication)

Also asserts:
- Full compliance with Google Rich Results specifications (@context, @type, publisher, inLanguage, logo, etc.)
- Author security and sanitization (anti-admin rewrite to VietnamGuide editorial team)
- Zero-CLS Image Dimension Resolution from inc/image-dimensions.php (620+ registered images, eager/lazy CWV attributes)
- Robust fail-closed handling of null, empty, or malformed contexts without PHP notices or broken output
- 100% route classification coverage matching ops/route_registry.json (282 canonical routes)
"""
import json
import os
import re
import subprocess
import tempfile
import unittest
from pathlib import Path

REPO_ROOT = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..')))
THEME_INC = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'inc'
MU_PLUGIN = REPO_ROOT / 'wordpress' / 'wp-content' / 'mu-plugins' / 'vietnamguide-core.php'
ROUTE_REGISTRY_JSON = REPO_ROOT / 'ops' / 'route_registry.json'
IMAGE_DIMS_PHP = THEME_INC / 'image-dimensions.php'
GUIDE_SEO_PHP = THEME_INC / 'guide-seo.php'


def _build_php_harness(php_body: str) -> str:
    """Build a standalone PHP harness script with complete WordPress environment mocks."""
    mu_path = str(MU_PLUGIN).replace('\\', '/')
    seo_path = str(GUIDE_SEO_PHP).replace('\\', '/')
    dims_path = str(IMAGE_DIMS_PHP).replace('\\', '/')

    return f"""<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

define('ABSPATH', 1);

if (! class_exists('WP_Post')) {{
    class WP_Post {{
        public $ID = 1;
        public $post_type = 'page';
        public $post_name = '';
        public $post_title = '';
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
                if (str_ends_with($raw, '/>')) {{
                    $raw = substr($raw, 0, -2) . ' ' . $attr_str . '/>';
                }} elseif (str_ends_with($raw, '>')) {{
                    $raw = substr($raw, 0, -1) . ' ' . $attr_str . '>';
                }}
            }}
        }}

        public function has_class(string $class): bool {{
            $c = $this->get_attribute('class');
            if ($c === null) {{
                return false;
            }}
            return in_array($class, preg_split('/\\s+/', trim($c)), true);
        }}

        public function get_updated_html(): string {{
            $out = '';
            foreach ($this->tokens as $tok) {{
                $out .= $tok['raw'];
            }}
            return $out;
        }}
    }}
}}

// WordPress Core Function Stubs
function add_action(...$args) {{}}
function add_filter(...$args) {{}}
function add_shortcode(...$args) {{}}
function apply_filters($tag, $val, ...$args) {{ return $val; }}
function get_theme_file_uri($rel = '') {{ return 'https://vietnamguide.net/wp-content/themes/vietnamguide-premium' . $rel; }}
function get_stylesheet_directory_uri() {{ return 'https://vietnamguide.net/wp-content/themes/vietnamguide-premium'; }}
function is_singular() {{ return true; }}
function is_admin() {{ return false; }}
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
function esc_attr__($s, $d = '') {{ return $s; }}
function esc_attr_e($s, $d = '') {{ echo $s; }}
function esc_html_e($s, $d = '') {{ echo $s; }}
function wp_strip_all_tags($s) {{ return strip_tags((string)$s); }}
function get_option($k, $d = '') {{ return $d; }}
function wp_safe_redirect($t, $s = 302) {{}}
function __($s, $d = '') {{ return $s; }}

require_once '{mu_path}';
require_once '{dims_path}';
require_once '{seo_path}';

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


def _execute_schema_pipeline(route_path: str, initial_data: dict = None, post_content: str = '') -> dict:
    """Run full Rank Math filter and Rich Travel Schema filter for a given route."""
    if initial_data is None:
        initial_data = {
            '@context': 'https://schema.org',
            '@graph': [
                {
                    '@type': 'Organization',
                    '@id': 'https://vietnamguide.net/#organization',
                    'name': 'VietnamGuide.net',
                    'url': 'https://vietnamguide.net/',
                },
                {
                    '@type': 'WebPage',
                    '@id': f'https://vietnamguide.net/{route_path}/#webpage',
                    'url': f'https://vietnamguide.net/{route_path}/',
                    'name': f'VietnamGuide - {route_path}',
                    'inLanguage': 'en-US',
                },
                {
                    '@type': 'Article',
                    '@id': f'https://vietnamguide.net/{route_path}/#article',
                    'headline': f'Verified Field Guide: {route_path}',
                    'description': 'Editorial route logistics and travel advisory.',
                    'inLanguage': 'en-US',
                    'author': {
                        '@type': 'Person',
                        'name': 'admin',  # Test sanitization
                    },
                    'publisher': {
                        '@id': 'https://vietnamguide.net/#organization',
                    },
                },
            ],
        }

    encoded_initial = json.dumps(initial_data)
    route_json = json.dumps(route_path)
    content_json = json.dumps(post_content)

    php_body = f"""
    $route = {route_json};
    $post = new WP_Post();
    $post->post_name = $route;
    $post->post_content = {content_json};
    $GLOBALS['post'] = $post;
    $GLOBALS['vg_test_qid'] = 1;
    $_SERVER['REQUEST_URI'] = '/' . ltrim($route, '/') . '/';

    $input_data = json_decode({json.dumps(encoded_initial)}, true);
    $step1 = vg_filter_rank_math_json_ld($input_data);
    $final_data = vg_rich_travel_schema_filter($step1);

    echo json_encode($final_data, JSON_UNESCAPED_SLASHES);
    """

    res = _run_php(php_body)
    if res.returncode != 0:
        raise RuntimeError(f'PHP Execution error:\nSTDOUT: {res.stdout}\nSTDERR: {res.stderr}')

    try:
        return json.loads(res.stdout)
    except json.JSONDecodeError as err:
        raise RuntimeError(f'Failed to parse JSON from PHP output: {res.stdout}') from err


class TestDestinationGuideSchema(unittest.TestCase):
    """Validates Schema.org JSON-LD generation for Destination Guides (TouristDestination)."""

    def test_destination_guides_generate_tourist_destination_entity(self):
        """Destination guides must emit valid TouristDestination entities with required Schema.org fields."""
        destinations = [
            'destinations/hanoi-travel-guide',
            'destinations/da-nang-travel-guide',
            'destinations/ha-long-bay-luxury-cruises',
            'destinations/ba-be-lake-travel-guide',
            'destinations/sapa-travel-guide',
        ]

        for path in destinations:
            with self.subTest(route=path):
                data = _execute_schema_pipeline(path)
                graph = data.get('@graph', [])
                self.assertIsInstance(graph, list, f'Graph must be a list for {path}')

                dest_nodes = [n for n in graph if n.get('@type') == 'TouristDestination']
                self.assertGreaterEqual(len(dest_nodes), 1, f'TouristDestination node missing for {path}')
                dest = dest_nodes[0]

                # Google Rich Results & Schema.org Specification assertions
                self.assertTrue(dest['@id'].endswith('#tourist-destination'))
                self.assertIsInstance(dest.get('name'), str)
                self.assertGreater(len(dest['name']), 2)
                self.assertIsInstance(dest.get('description'), str)
                self.assertGreater(len(dest['description']), 20)
                self.assertEqual(dest.get('currenciesAccepted'), 'VND')
                self.assertEqual(dest.get('availableLanguage'), ['en', 'vi'])
                self.assertTrue(dest.get('publicAccess'))
                self.assertFalse(dest.get('isAccessibleForFree'))
                self.assertIn('containedInPlace', dest)
                self.assertIn('itinerary', dest)
                self.assertIn('potentialAction', dest)

    def test_destination_geographic_and_attraction_metadata(self):
        """Destination guides must contain GeoCoordinates, hasMap link, and TouristAttraction entities."""
        data = _execute_schema_pipeline('destinations/hanoi-travel-guide')
        graph = data.get('@graph', [])
        dest = [n for n in graph if n.get('@type') == 'TouristDestination'][0]

        # Geographic coordinates
        self.assertIn('geo', dest)
        self.assertEqual(dest['geo'].get('@type'), 'GeoCoordinates')
        self.assertIsInstance(dest['geo'].get('latitude'), (float, int))
        self.assertIsInstance(dest['geo'].get('longitude'), (float, int))
        self.assertAlmostEqual(dest['geo']['latitude'], 21.0285, places=3)
        self.assertAlmostEqual(dest['geo']['longitude'], 105.8542, places=3)

        # OpenStreetMap URL
        self.assertIn('hasMap', dest)
        self.assertIn('openstreetmap.org', dest['hasMap'])

        # PostalAddress
        self.assertIn('address', dest)
        self.assertEqual(dest['address'].get('@type'), 'PostalAddress')
        self.assertEqual(dest['address'].get('addressCountry'), 'VN')
        self.assertEqual(dest['address'].get('addressRegion'), 'Hanoi')

        # Included attractions
        self.assertIn('includesAttraction', dest)
        attractions = dest['includesAttraction']
        self.assertIsInstance(attractions, list)
        self.assertGreaterEqual(len(attractions), 3)
        for attr in attractions:
            self.assertEqual(attr.get('@type'), 'TouristAttraction')
            self.assertIn('name', attr)
            self.assertTrue(attr.get('sameAs', '').startswith('https://www.wikidata.org/wiki/'))

    def test_destination_cluster_resolution_all_clusters(self):
        """All 8 travel clusters in registry must resolve accurately with complete schemas."""
        cluster_samples = {
            'northern_triangle': 'destinations/hanoi-travel-guide',
            'ha_long_bay': 'destinations/ha-long-bay-luxury-cruises',
            'ninh_binh': 'destinations/ninh-binh-travel-guide',
            'northern_highlands': 'destinations/sapa-travel-guide',
            'central_heritage': 'destinations/da-nang-travel-guide',
            'southern_delta': 'destinations/ho-chi-minh-city-travel-guide',
            'coastal_islands': 'destinations/phu-quoc-travel-guide',
            'national_circuit': 'destinations/vietnam-highlights',
        }

        for cluster_id, sample_path in cluster_samples.items():
            with self.subTest(cluster=cluster_id):
                data = _execute_schema_pipeline(sample_path)
                dest = [n for n in data['@graph'] if n.get('@type') == 'TouristDestination'][0]
                self.assertIsNotNone(dest.get('name'))
                self.assertIsNotNone(dest.get('description'))
                self.assertIsNotNone(dest.get('containedInPlace'))

    def test_destination_links_to_article_and_webpage_via_about(self):
        """Article and WebPage nodes must link to TouristDestination via the 'about' property."""
        data = _execute_schema_pipeline('destinations/da-nang-travel-guide')
        graph = data['@graph']
        dest = [n for n in graph if n.get('@type') == 'TouristDestination'][0]
        dest_id = dest['@id']

        article = [n for n in graph if n.get('@type') == 'Article'][0]
        webpage = [n for n in graph if n.get('@type') == 'WebPage'][0]

        # Verify CreativeWork.about linkage
        self.assertIn('about', article)
        about_ids = [a['@id'] for a in (article['about'] if isinstance(article['about'], list) else [article['about']]) if isinstance(a, dict) and '@id' in a]
        self.assertIn(dest_id, about_ids)

        self.assertIn('about', webpage)
        page_about_ids = [a['@id'] for a in (webpage['about'] if isinstance(webpage['about'], list) else [webpage['about']]) if isinstance(a, dict) and '@id' in a]
        self.assertIn(dest_id, page_about_ids)


class TestRouteComparisonSchema(unittest.TestCase):
    """Validates Schema.org JSON-LD generation for Route Comparisons (Article, FAQPage, multi-destination entities)."""

    def test_comparison_guides_generate_article_with_multi_destination_mentions(self):
        """Comparison routes must identify both comparison destinations and inject them into 'mentions'."""
        comparisons = [
            ('compare/con-dao-vs-phu-quoc', ['Con Dao', 'Phu Quoc']),
            ('compare/hanoi-vs-ho-chi-minh-city', ['Hanoi', 'Ho Chi Minh City']),
            ('compare/trang-an-vs-tam-coc', ['Trang An', 'Tam Coc']),
            ('compare/ha-long-bay-vs-lan-ha-bay', ['Ha Long Bay', 'Lan Ha Bay']),
            ('compare/da-nang-vs-hoi-an', ['Hoi An', 'Da Nang']),
        ]

        for path, expected_entities in comparisons:
            with self.subTest(route=path):
                data = _execute_schema_pipeline(path)
                graph = data['@graph']
                article = [n for n in graph if n.get('@type') == 'Article'][0]

                self.assertIn('mentions', article)
                mentions = article['mentions']
                mention_names = [m.get('name') for m in mentions if isinstance(m, dict)]

                for expected in expected_entities:
                    self.assertIn(
                        expected,
                        mention_names,
                        f"Expected entity '{expected}' missing from article mentions for {path}. Found: {mention_names}"
                    )

    def test_comparison_guides_authoritative_faq_schema(self):
        """Curated comparison routes must generate authoritative FAQPage nodes with questions and answers."""
        faq_comparisons = [
            'compare/trang-an-vs-tam-coc',
            'compare/ha-long-bay-vs-lan-ha-bay',
            'compare/da-nang-vs-hoi-an',
            'compare/ha-giang-easy-rider-vs-self-drive',
        ]

        for path in faq_comparisons:
            with self.subTest(route=path):
                data = _execute_schema_pipeline(path)
                graph = data['@graph']
                faq_nodes = [n for n in graph if n.get('@type') == 'FAQPage']
                self.assertEqual(len(faq_nodes), 1, f'FAQPage missing for comparison route {path}')
                faq = faq_nodes[0]

                self.assertIn('mainEntity', faq)
                questions = faq['mainEntity']
                self.assertGreaterEqual(len(questions), 2, f'FAQPage for {path} should have multiple questions')

                for q in questions:
                    self.assertEqual(q.get('@type'), 'Question')
                    self.assertIsInstance(q.get('name'), str)
                    self.assertGreater(len(q['name']), 10)
                    self.assertIn('acceptedAnswer', q)
                    self.assertEqual(q['acceptedAnswer'].get('@type'), 'Answer')
                    self.assertIsInstance(q['acceptedAnswer'].get('text'), str)
                    self.assertGreater(len(q['acceptedAnswer']['text']), 20)

    def test_comparison_article_editorial_and_publisher_contracts(self):
        """Comparison articles must declare reviewedBy, publisher with 512x512 logo, speakable, and breadcrumb."""
        data = _execute_schema_pipeline('compare/con-dao-vs-phu-quoc')
        article = [n for n in data['@graph'] if n.get('@type') == 'Article'][0]

        # Editorial reviewedBy
        self.assertIn('reviewedBy', article)
        self.assertEqual(article['reviewedBy'].get('@type'), 'Person')
        self.assertEqual(article['reviewedBy'].get('name'), 'VietnamGuide editorial team')
        self.assertTrue(article['reviewedBy'].get('url', '').endswith('/editorial-policy/'))

        # Publisher logo explicit dimensions (CLS = 0)
        self.assertIn('publisher', article)
        logo = article['publisher'].get('logo', {})
        self.assertEqual(logo.get('@type'), 'ImageObject')
        self.assertEqual(logo.get('width'), 512)
        self.assertEqual(logo.get('height'), 512)
        self.assertEqual(logo.get('inLanguage'), 'en-US')

        # Speakable specification
        self.assertIn('speakable', article)
        self.assertEqual(article['speakable'].get('@type'), 'SpeakableSpecification')
        self.assertIn('.vg-concierge-verdict', article['speakable'].get('cssSelector', []))

        # Breadcrumb reference
        self.assertIn('breadcrumb', article)
        self.assertTrue(article['breadcrumb'].get('@id', '').endswith('#breadcrumb'))


class TestItineraryGuideSchema(unittest.TestCase):
    """Validates Schema.org JSON-LD generation for Itineraries (TouristTrip, multi-stop ItemList)."""

    def test_itinerary_guides_generate_tourist_trip_with_multistop_itinerary(self):
        """Itineraries must emit TouristTrip entities with sequential day/stop ItemList and Free Offer."""
        itineraries = [
            'itineraries/10-days-in-vietnam',
            'itineraries/14-days-in-vietnam',
            'itineraries/21-days-in-vietnam',
        ]

        for path in itineraries:
            with self.subTest(route=path):
                data = _execute_schema_pipeline(path)
                graph = data['@graph']
                trips = [n for n in graph if n.get('@type') == 'TouristTrip']
                self.assertEqual(len(trips), 1, f'TouristTrip missing for {path}')
                trip = trips[0]

                # Top-level trip properties
                self.assertTrue(trip['@id'].endswith('#tourist-trip'))
                self.assertIsInstance(trip.get('name'), str)
                self.assertGreater(len(trip['name']), 5)
                self.assertIsInstance(trip.get('description'), str)
                self.assertGreater(len(trip['description']), 20)
                self.assertEqual(trip.get('provider', {}).get('name'), 'VietnamGuide.net')

                # Free Editorial Route Planning Offer
                self.assertIn('offers', trip)
                offer = trip['offers']
                self.assertEqual(offer.get('@type'), 'Offer')
                self.assertEqual(offer.get('price'), '0')
                self.assertEqual(offer.get('priceCurrency'), 'USD')
                self.assertEqual(offer.get('category'), 'Free Editorial Route Planning')

                # Sequential multi-stop itinerary
                self.assertIn('itinerary', trip)
                itinerary = trip['itinerary']
                self.assertEqual(itinerary.get('@type'), 'ItemList')
                self.assertGreaterEqual(itinerary.get('numberOfItems', 0), 3)

                elements = itinerary.get('itemListElement', [])
                self.assertEqual(len(elements), itinerary.get('numberOfItems'))
                for idx, elem in enumerate(elements, start=1):
                    self.assertEqual(elem.get('@type'), 'ListItem')
                    self.assertEqual(elem.get('position'), idx)
                    self.assertEqual(elem.get('item', {}).get('@type'), 'TouristDestination')
                    self.assertTrue(len(elem['item'].get('name', '')) > 1)
                    self.assertTrue(elem['item'].get('url', '').startswith('https://'))

    def test_itinerary_travel_action_from_and_to_locations(self):
        """Itineraries must include a TravelAction node with origin, destination stops, and result link."""
        data = _execute_schema_pipeline('itineraries/14-days-in-vietnam')
        graph = data['@graph']
        actions = [n for n in graph if n.get('@type') == 'TravelAction']
        self.assertEqual(len(actions), 1)
        action = actions[0]

        self.assertEqual(action.get('actionStatus'), 'https://schema.org/PotentialActionStatus')
        self.assertEqual(action.get('fromLocation', {}).get('@type'), 'TouristDestination')
        self.assertIsInstance(action.get('toLocation'), list)
        self.assertGreaterEqual(len(action['toLocation']), 1)
        self.assertTrue(action.get('result', {}).get('@id', '').endswith('#tourist-trip'))


class TestPracticalEssentialsSchema(unittest.TestCase):
    """Validates Schema.org JSON-LD generation for Practical Essentials (FAQPage, HowTo, WebApplication)."""

    def test_practical_guides_generate_faq_howto_webapplication(self):
        """The vietnam-evisa guide must output FAQPage, HowTo, and WebApplication entities simultaneously."""
        data = _execute_schema_pipeline('plan/vietnam-evisa')
        graph = data['@graph']
        node_types = [n.get('@type') for n in graph]

        self.assertIn('FAQPage', node_types, 'FAQPage missing on vietnam-evisa')
        self.assertIn('HowTo', node_types, 'HowTo missing on vietnam-evisa')
        self.assertIn('WebApplication', node_types, 'WebApplication missing on vietnam-evisa')

        # Check FAQPage
        faq = [n for n in graph if n.get('@type') == 'FAQPage'][0]
        self.assertEqual(len(faq.get('mainEntity', [])), 5)

        # Check HowTo
        howto = [n for n in graph if n.get('@type') == 'HowTo'][0]
        self.assertEqual(howto.get('name'), 'How to Apply for a Vietnam E-Visa Online')
        self.assertEqual(len(howto.get('step', [])), 5)
        for i, step in enumerate(howto['step'], start=1):
            self.assertEqual(step.get('@type'), 'HowToStep')
            self.assertEqual(int(step.get('position')), i)
            self.assertTrue(step.get('url', '').endswith(f'#step-{i}'))
            self.assertGreater(len(step.get('name', '')), 3)
            self.assertGreater(len(step.get('text', '')), 20)

        # Check WebApplication
        webapp = [n for n in graph if n.get('@type') == 'WebApplication'][0]
        self.assertEqual(webapp.get('name'), 'Vietnam Visa Requirement & Exemption Checker')
        self.assertEqual(webapp.get('applicationCategory'), 'TravelApplication')
        self.assertEqual(len(webapp.get('featureList', [])), 5)

    def test_sim_esim_practical_guide_schema(self):
        """The sim-esim-vietnam guide must generate valid FAQPage and HowTo schemas."""
        data = _execute_schema_pipeline('plan/sim-esim-vietnam')
        graph = data['@graph']

        faq = [n for n in graph if n.get('@type') == 'FAQPage'][0]
        self.assertEqual(len(faq.get('mainEntity', [])), 3)

        howto = [n for n in graph if n.get('@type') == 'HowTo'][0]
        self.assertEqual(howto.get('name'), 'How to Buy and Set Up an eSIM for Vietnam')
        self.assertEqual(len(howto.get('step', [])), 4)

    def test_travel_cost_and_weather_matrix_webapplication(self):
        """Travel cost calculator and weather matrix must emit interactive WebApplication schemas."""
        data_cost = _execute_schema_pipeline('plan/vietnam-travel-cost')
        webapp_cost = [n for n in data_cost['@graph'] if n.get('@type') == 'WebApplication'][0]
        self.assertEqual(webapp_cost.get('name'), 'Vietnam Travel Cost Calculator')
        self.assertIn('5-currency', webapp_cost.get('description', ''))
        self.assertGreaterEqual(len(webapp_cost.get('featureList', [])), 5)

        data_weather = _execute_schema_pipeline('plan/best-time-to-visit-vietnam')
        webapp_weather = [n for n in data_weather['@graph'] if n.get('@type') == 'WebApplication'][0]
        self.assertEqual(webapp_weather.get('name'), 'Vietnam Weather & Regional Season Matrix')
        self.assertEqual(webapp_weather.get('applicationCategory'), 'TravelApplication')

    def test_packing_checklist_webapplication(self):
        """First trip checklist guide must emit WebApplication schema with state persistence features."""
        data = _execute_schema_pipeline('plan/vietnam-first-trip-planning-checklist')
        webapp = [n for n in data['@graph'] if n.get('@type') == 'WebApplication'][0]
        self.assertEqual(webapp.get('name'), 'Vietnam Route Packing & Preparation Checklist')
        self.assertEqual(len(webapp.get('featureList', [])), 5)


class TestGoogleRichResultsCompliance(unittest.TestCase):
    """Validates full compliance with Google Rich Results specifications."""

    def test_top_level_context_and_type_declarations(self):
        """All schema output documents must declare @context https://schema.org and valid vocabulary types."""
        routes = [
            'destinations/hanoi-travel-guide',
            'compare/con-dao-vs-phu-quoc',
            'itineraries/10-days-in-vietnam',
            'plan/vietnam-evisa',
        ]

        allowed_types = {
            'TouristDestination', 'TouristTrip', 'TravelAction', 'Article', 'WebPage',
            'Organization', 'Person', 'FAQPage', 'HowTo', 'WebApplication', 'ImageObject',
            'SpeakableSpecification', 'Offer', 'ItemList', 'ListItem', 'PostalAddress',
            'GeoCoordinates', 'Place', 'Country', 'TouristAttraction', 'Question', 'Answer',
            'HowToStep', 'City', 'AdministrativeArea', 'NationalPark', 'Thing'
        }

        for path in routes:
            with self.subTest(route=path):
                data = _execute_schema_pipeline(path)
                self.assertEqual(data.get('@context'), 'https://schema.org')
                graph = data.get('@graph', [])
                self.assertGreater(len(graph), 0)

                for node in graph:
                    node_type = node.get('@type')
                    if isinstance(node_type, list):
                        for t in node_type:
                            self.assertIn(t, allowed_types)
                    else:
                        self.assertIn(node_type, allowed_types)

    def test_google_rich_results_required_fields_article(self):
        """Article nodes must meet Google Rich Results specification (headline, inLanguage, publisher, logo)."""
        data = _execute_schema_pipeline('compare/hanoi-vs-ho-chi-minh-city')
        article = [n for n in data['@graph'] if n.get('@type') == 'Article'][0]

        self.assertIn('headline', article)
        self.assertIn('inLanguage', article)
        self.assertEqual(article['inLanguage'], 'en-US')
        self.assertIn('publisher', article)
        logo = article['publisher'].get('logo', {})
        self.assertEqual(logo.get('@type'), 'ImageObject')
        self.assertEqual(logo.get('width'), 512)
        self.assertEqual(logo.get('height'), 512)
        self.assertTrue(logo.get('url', '').endswith('.png'))

    def test_google_rich_results_required_fields_faqpage(self):
        """FAQPage nodes must comply with Google Search Rich Results guidelines."""
        data = _execute_schema_pipeline('plan/vietnam-airport-arrival-checklist')
        faq = [n for n in data['@graph'] if n.get('@type') == 'FAQPage'][0]

        self.assertIn('mainEntity', faq)
        for question in faq['mainEntity']:
            self.assertEqual(question.get('@type'), 'Question')
            self.assertGreater(len(question.get('name', '')), 10)
            self.assertEqual(question.get('acceptedAnswer', {}).get('@type'), 'Answer')
            self.assertGreater(len(question['acceptedAnswer'].get('text', '')), 20)

    def test_author_security_and_editorial_sanitization(self):
        """Forbidden author names (e.g. 'admin') must be sanitized to 'VietnamGuide editorial team'."""
        dirty_initial = {
            '@context': 'https://schema.org',
            '@graph': [
                {
                    '@type': 'Article',
                    'headline': 'Test Guide',
                    'author': {
                        '@type': 'Person',
                        'name': 'admin',
                    },
                },
            ],
        }

        data = _execute_schema_pipeline('destinations/hue-travel-guide', initial_data=dirty_initial)
        article = [n for n in data['@graph'] if n.get('@type') == 'Article'][0]
        self.assertEqual(article['author']['name'], 'VietnamGuide editorial team')


class TestZeroClsImageDimensions(unittest.TestCase):
    """Validates image dimensions resolution from inc/image-dimensions.php for zero Cumulative Layout Shift."""

    def test_image_dimensions_registry_integrity(self):
        """Image dimensions registry must contain >= 620 entries with valid positive widths and heights."""
        php_body = """
        $dims = vg_get_known_image_dimensions();
        $count = count($dims);
        $invalid = 0;
        foreach ($dims as $key => $dim) {
            if (! is_array($dim) || count($dim) !== 2 || $dim[0] < 100 || $dim[1] < 100) {
                $invalid++;
            }
        }
        echo json_encode(['count' => $count, 'invalid' => $invalid]);
        """
        res = _run_php(php_body)
        self.assertEqual(res.returncode, 0, res.stderr)
        data = json.loads(res.stdout)
        self.assertGreaterEqual(data['count'], 620, 'Known image dimensions must register >= 620 entries')
        self.assertEqual(data['invalid'], 0, 'All image dimensions must be positive numbers >= 100px')

    def test_enhance_content_images_injects_dimensions_and_cwv_attributes(self):
        """vg_enhance_content_images must inject width, height, loading=lazy, and decoding=async."""
        raw_html = (
            '<p>Exploring Saigon:</p>'
            '<p><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/'
            '1280px-Ben_Thanh_Market,_2023_(03).jpg/1280px-Ben_Thanh_Market,_2023_(03).jpg" '
            'alt="Ben Thanh Market"></p>'
        )

        php_body = f"""
        $html = {json.dumps(raw_html)};
        $enhanced = vg_enhance_content_images($html);
        echo json_encode(['enhanced' => $enhanced]);
        """
        res = _run_php(php_body)
        self.assertEqual(res.returncode, 0, res.stderr)
        enhanced = json.loads(res.stdout)['enhanced']

        self.assertIn('width="1280"', enhanced)
        self.assertIn('height="1280"', enhanced)
        self.assertIn('loading="lazy"', enhanced)
        self.assertIn('decoding="async"', enhanced)

    def test_hero_image_optimization_lcp_eager(self):
        """Hero images must receive loading=eager and fetchpriority=high to optimize LCP without CLS."""
        raw_html = (
            '<div class="vg-guide-hero">'
            '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/'
            '1280px-Ben_Thanh_Market,_2023_(03).jpg/1280px-Ben_Thanh_Market,_2023_(03).jpg" '
            'alt="Ben Thanh Hero">'
            '</div>'
        )

        php_body = f"""
        $html = {json.dumps(raw_html)};
        $enhanced = vg_enhance_content_images($html);
        echo json_encode(['enhanced' => $enhanced]);
        """
        res = _run_php(php_body)
        self.assertEqual(res.returncode, 0, res.stderr)
        enhanced = json.loads(res.stdout)['enhanced']

        self.assertIn('loading="eager"', enhanced)
        self.assertIn('fetchpriority="high"', enhanced)
        self.assertIn('width="1280"', enhanced)
        self.assertIn('height="1280"', enhanced)

    def test_unindexed_wikimedia_thumbnail_fallback(self):
        """Unindexed Wikimedia thumbnail images must fall back to 2/3 ratio heuristic without shifting layout."""
        raw_html = '<p><img src="https://upload.wikimedia.org/wikipedia/commons/1280px-unindexed-sample.jpg" alt="Sample"></p>'

        php_body = f"""
        $html = {json.dumps(raw_html)};
        $enhanced = vg_enhance_content_images($html);
        echo json_encode(['enhanced' => $enhanced]);
        """
        res = _run_php(php_body)
        self.assertEqual(res.returncode, 0, res.stderr)
        enhanced = json.loads(res.stdout)['enhanced']

        self.assertIn('width="1280"', enhanced)
        self.assertIn('height="853"', enhanced)


class TestSchemaRobustnessAndNullContext(unittest.TestCase):
    """Validates fail-closed handling of null, empty, or malformed contexts."""

    def test_schema_filter_handles_null_empty_and_scalar_inputs(self):
        """Filter must safely handle null, empty arrays, or scalar types without PHP fatal errors."""
        php_body = """
        $cases = [
            'null'         => null,
            'empty_string' => '',
            'empty_array'  => [],
            'scalar_int'   => 42,
            'empty_graph'  => ['@graph' => []],
        ];

        $results = [];
        foreach ($cases as $k => $c) {
            $r1 = vg_filter_rank_math_json_ld((array)$c);
            $r2 = vg_rich_travel_schema_filter($r1, null);
            $results[$k] = is_array($r2);
        }
        echo json_encode($results);
        """
        res = _run_php(php_body)
        self.assertEqual(res.returncode, 0, res.stderr)
        results = json.loads(res.stdout)
        for k, success in results.items():
            self.assertTrue(success, f'Filter failed on case: {k}')

    def test_schema_filter_handles_missing_request_uri_and_post(self):
        """When REQUEST_URI and post object are absent, filter falls back to national_circuit gracefully."""
        php_body = """
        unset($_SERVER['REQUEST_URI']);
        $GLOBALS['post'] = null;
        $GLOBALS['vg_test_qid'] = 0;

        $data = ['@graph' => []];
        $out = vg_rich_travel_schema_filter($data, null);
        $types = array_column($out['@graph'], '@type');
        echo json_encode(['types' => $types]);
        """
        res = _run_php(php_body)
        self.assertEqual(res.returncode, 0, res.stderr)
        types = json.loads(res.stdout)['types']
        self.assertIn('TouristDestination', types)
        self.assertIn('TouristTrip', types)

    def test_idempotent_schema_filter_invocation(self):
        """Invoking vg_rich_travel_schema_filter multiple times must not duplicate TouristDestination nodes."""
        php_body = """
        $_SERVER['REQUEST_URI'] = '/destinations/hanoi-travel-guide/';
        $post = new WP_Post();
        $post->post_name = 'destinations/hanoi-travel-guide';
        $GLOBALS['post'] = $post;

        $data = ['@graph' => []];
        $pass1 = vg_rich_travel_schema_filter($data);
        $pass2 = vg_rich_travel_schema_filter($pass1);

        $dest_count = 0;
        foreach ($pass2['@graph'] as $node) {
            if (($node['@type'] ?? '') === 'TouristDestination') {
                $dest_count++;
            }
        }
        echo json_encode(['dest_count' => $dest_count]);
        """
        res = _run_php(php_body)
        self.assertEqual(res.returncode, 0, res.stderr)
        count = json.loads(res.stdout)['dest_count']
        self.assertEqual(count, 1, 'TouristDestination node must not be duplicated on repeated calls')


class TestRouteRegistryClassificationsCoverage(unittest.TestCase):
    """Validates that all 4 guide classifications in ops/route_registry.json are covered."""

    def test_route_registry_classifications_presence(self):
        """Route registry must contain exactly 282 canonical routes across the 4 classifications."""
        self.assertTrue(ROUTE_REGISTRY_JSON.is_file(), f'Missing {ROUTE_REGISTRY_JSON}')
        with open(ROUTE_REGISTRY_JSON, 'r', encoding='utf-8') as f:
            reg_data = json.load(f)

        routes = reg_data.get('routes', [])
        self.assertEqual(len(routes), 282, f'Expected 282 routes, found {len(routes)}')

        counts_by_type = {}
        for r in routes:
            t = r.get('type')
            counts_by_type[t] = counts_by_type.get(t, 0) + 1

        self.assertEqual(counts_by_type.get('destination'), 117)
        self.assertEqual(counts_by_type.get('comparison'), 19)
        self.assertEqual(counts_by_type.get('itinerary'), 14)
        self.assertEqual(counts_by_type.get('practical'), 132)

    def test_sampled_routes_from_each_classification_pass_schema_generation(self):
        """Sampled routes from each classification must execute cleanly through the schema pipeline."""
        with open(ROUTE_REGISTRY_JSON, 'r', encoding='utf-8') as f:
            routes = json.load(f).get('routes', [])

        for c_type in ['destination', 'comparison', 'itinerary', 'practical']:
            matches = [r for r in routes if r.get('type') == c_type]
            sample = matches[0]['path']
            with self.subTest(classification=c_type, route=sample):
                data = _execute_schema_pipeline(sample)
                self.assertIn('@graph', data)
                self.assertGreater(len(data['@graph']), 0)


if __name__ == '__main__':
    unittest.main()
