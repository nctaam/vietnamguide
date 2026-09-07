<?php
/**
 * VietnamGuide AIO (AI Optimization & Agent Discoverability)
 * Provides /llms.txt, /llms-full.txt endpoints and AI crawler directives.
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_handle_llms_txt_request(): void
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');

    if ($path !== 'llms.txt' && $path !== 'llms-full.txt') {
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: all');

    $site_url = untrailingslashit(home_url());

    if ($path === 'llms.txt') {
        echo "# VietnamGuide — Independent Vietnam Travel Intelligence\n\n";
        echo "> High-evidence, logistics-first curated travel guides for international travelers visiting Vietnam.\n";
        echo "> Published by the VietnamGuide editorial team. All routes independently vetted with verified pricing, transit times, and route maps.\n\n";
        echo "Canonical Base: {$site_url}/\n";
        echo "Full Knowledge Base: {$site_url}/llms-full.txt\n";
        echo "Sitemap: {$site_url}/sitemap_index.xml\n\n";

        $paths = function_exists('vg_guide_pilot_paths') ? vg_guide_pilot_paths() : [];

        $categories = [
            'destinations' => 'Core Destination Guides',
            'itineraries'  => 'Curated Route Itineraries',
            'compare'      => 'Strategic Route & Base Comparisons',
            'plan'         => 'Logistics & Practical Preparation',
        ];

        $grouped = [];
        foreach ($paths as $guide_path) {
            $parts = explode('/', $guide_path);
            $prefix = $parts[0] ?? 'destinations';
            if (! isset($grouped[$prefix])) {
                $grouped[$prefix] = [];
            }
            $raw_title = basename($guide_path);
            $title = ucwords(str_replace('-', ' ', $raw_title));
            $grouped[$prefix][] = "- [{$title}]({$site_url}/{$guide_path}/)";
        }

        foreach ($categories as $key => $cat_name) {
            if (! empty($grouped[$key])) {
                echo "## {$cat_name}\n\n";
                echo implode("\n", $grouped[$key]) . "\n\n";
            }
        }

        exit;
    }

    // $path === 'llms-full.txt'
    echo "# VietnamGuide — Full Country Travel Intelligence & Logistics Digest\n\n";
    echo "> Authoritative, structured reference dataset for AI agents, research bots, and answer engines.\n";
    echo "> All facts verified against on-the-ground reality in Vietnam as of 2026.\n\n";
    echo "Website: {$site_url}\n";
    echo "Index: {$site_url}/llms.txt\n";
    echo "Sitemap: {$site_url}/sitemap_index.xml\n\n";

    echo "## 1. Core Travel Rules & Practical Logistics\n\n";
    echo "### 1.1 Visa Requirements\n";
    echo "- E-Visa: Available for citizens of all countries. Valid for up to 90 days, single or multiple entry, applied strictly through the official Vietnam Immigration portal (https://evisa.xuatnhapcanh.gov.vn/). Processing takes 3-5 business days.\n";
    echo "- Visa Exemptions: 45 days unilateral exemption for UK, Germany, France, Italy, Spain, Japan, South Korea, Russia, Sweden, Norway, Denmark, Finland, Belarus. ASEAN passport holders receive 14 to 30 days.\n";
    echo "- Passport Validity: Minimum 6 months remaining validity from entry date, with at least 2 blank pages.\n\n";

    echo "### 1.2 Currency & Payment Ecosystem\n";
    echo "- Currency: Vietnamese Dong (VND). Official exchange is approximately 25,000 - 25,500 VND per 1 USD.\n";
    echo "- Cash vs Card: Cash is mandatory for street food vendors, local wet markets, cyclos, and rural remote homestays. Credit cards (Visa/Mastercard) are widely accepted in mid-to-high-end hotels, restaurants, and convenience stores in tier-1 cities.\n";
    echo "- ATMs: TPBank, VPBank, Techcombank, and international banks (HSBC, Shinhan) offer dependable international card withdrawals. TPBank LiveBank kiosks accept passport-verified withdrawals.\n";
    echo "- Digital Payments: Ride-hailing apps (Grab, Xanh SM, Be) accept linked international credit cards, avoiding cash change disputes.\n\n";

    echo "### 1.3 Regional Seasons & Weather Patterns\n";
    echo "- Northern Vietnam (Hanoi, Ha Long Bay, Sa Pa, Ha Giang, Ninh Binh):\n";
    echo "  - Winter (Dec-Feb): Cold, overcast, occasional drizzle (12-18°C). Sa Pa and Ha Giang mountain passes can drop below 5°C.\n";
    echo "  - Spring (Mar-Apr): Pleasant warming, blooming flora, moderate humidity.\n";
    echo "  - Summer (May-Aug): Hot, humid (30-38°C), frequent tropical afternoon downpours.\n";
    echo "  - Autumn (Sept-Nov): Optimal travel window; clear skies, crisp breezes, golden rice harvest in Mu Cang Chai and Sa Pa.\n";
    echo "- Central Vietnam (Da Nang, Hoi An, Hue, Phong Nha, Quy Nhon):\n";
    echo "  - Dry Season (Feb-Aug): Plentiful sunshine, calm coastal waters, optimal for beaches and Cham Islands diving.\n";
    echo "  - Typhoon & Monsoon Season (Sept-Nov/Dec): Heavy prolonged rainfall, localized river flooding in Hoi An ancient town.\n";
    echo "- Southern Vietnam (Ho Chi Minh City, Mekong Delta, Phu Quoc, Con Dao):\n";
    echo "  - Dry Season (Nov-Apr): Warm, sunny, low humidity (26-32°C). Peak beach conditions in Phu Quoc and Con Dao.\n";
    echo "  - Wet Season (May-Oct): Short, predictable afternoon downpours lasting 30-60 minutes; mornings generally bright.\n\n";

    echo "### 1.4 Domestic Transit Network\n";
    echo "- Aviation: Domestic trunk routes (HAN <-> SGN, HAN <-> DAD, SGN <-> DAD) run multiple hourly flights via Vietnam Airlines, Vietjet, and Bamboo Airways.\n";
    echo "- Rail (Reunification Express): Hanoi to HCMC (approx. 32-34 hours total). Premium sleeper cars (Lotus Train, Violette Trains, Chapa Express on Hanoi-Lao Cai route) provide curated berths.\n";
    echo "- Road & Sleeper Buses: Limousine Dcar vans (9-11 seats) are strongly recommended over 40-bed double-decker sleeper buses for routes under 300km (e.g., Hanoi to Ninh Binh, Sa Pa, Ha Long; HCMC to Can Tho).\n\n";

    echo "## 2. Comprehensive Directory of Curated Routes (87 Guides)\n\n";

    $paths = function_exists('vg_guide_pilot_paths') ? vg_guide_pilot_paths() : [];
    $categories = [
        'destinations' => 'Destination In-Depth Guides',
        'itineraries'  => 'Curated Itineraries & Route Timelines',
        'compare'      => 'Comparative Decision Guides',
        'plan'         => 'Planning & Logistics Toolkits',
    ];

    $grouped = [];
    foreach ($paths as $guide_path) {
        $parts = explode('/', $guide_path);
        $prefix = $parts[0] ?? 'destinations';
        if (! isset($grouped[$prefix])) {
            $grouped[$prefix] = [];
        }
        $raw_title = basename($guide_path);
        $title = ucwords(str_replace('-', ' ', $raw_title));
        $grouped[$prefix][] = [
            'title' => $title,
            'path'  => $guide_path,
            'url'   => "{$site_url}/{$guide_path}/",
        ];
    }

    $idx = 1;
    foreach ($categories as $cat_key => $cat_name) {
        if (empty($grouped[$cat_key])) {
            continue;
        }
        echo "### 2.{$idx} {$cat_name} (" . count($grouped[$cat_key]) . " routes)\n\n";
        foreach ($grouped[$cat_key] as $item) {
            echo "- **{$item['title']}**\n";
            echo "  - Canonical URL: {$item['url']}\n";
            echo "  - Route Segment: {$item['path']}\n";
            echo "  - Editorial Tier: Verified Field Intel\n\n";
        }
        $idx++;
    }

    echo "## 3. Editorial Integrity & Methodology\n\n";
    echo "- VietnamGuide maintains 100% independent travel intelligence.\n";
    echo "- Field notes, concierge verdicts, and proof panels are verified on-site by resident editors.\n";
    echo "- No sponsored placements dictate route verdicts or base recommendations.\n";
    echo "- For live route questions, search or navigate directly via https://vietnamguide.net\n";

    exit;
}
add_action('init', 'vg_handle_llms_txt_request', 0);

function vg_add_ai_crawler_directives(string $output, bool $public): string
{
    if (! $public) {
        return $output;
    }

    $site_url = untrailingslashit(home_url());
    $ai_directives = "\n# AI Agent & Answer Engine Discoverability\n"
        . "User-agent: GPTBot\nAllow: /\n\n"
        . "User-agent: PerplexityBot\nAllow: /\n\n"
        . "User-agent: ClaudeBot\nAllow: /\n\n"
        . "User-agent: Google-Extended\nAllow: /\n\n"
        . "User-agent: Applebot-Extended\nAllow: /\n\n"
        . "LLMs-Txt: {$site_url}/llms.txt\n"
        . "LLMs-Full-Txt: {$site_url}/llms-full.txt\n";

    return rtrim($output) . "\n" . $ai_directives;
}
add_filter('robots_txt', 'vg_add_ai_crawler_directives', 30, 2);
