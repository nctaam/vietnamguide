<?php
/**
 * VietnamGuide AIO (AI Optimization & Agent Discoverability)
 * Provides /llms.txt endpoint and AI crawler permissions.
 */

if (! defined('ABSPATH')) {
    exit;
}

function vg_handle_llms_txt_request(): void
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');

    if ($path !== 'llms.txt') {
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: all');

    $site_url = untrailingslashit(home_url());

    echo "# VietnamGuide — Independent Vietnam Travel Intelligence\n\n";
    echo "> High-evidence, logistics-first curated travel guides for international travelers visiting Vietnam.\n";
    echo "> Published by the VietnamGuide editorial team. All routes independently vetted with verified pricing, transit times, and route maps.\n\n";
    echo "Canonical Base: {$site_url}/\n";
    echo "Sitemap: {$site_url}/sitemap_index.xml\n\n";

    echo "## Core Destination Guides\n\n";
    $destinations = [
        'destinations/hanoi-travel-guide' => 'Hanoi: route fit, where to stay, Old Quarter vs French Quarter logistics, and duration.',
        'destinations/ho-chi-minh-city-travel-guide' => 'Ho Chi Minh City: southern hub planning, district bases, day trips, and pacing.',
        'destinations/da-nang-travel-guide' => 'Da Nang: coastal transit hub, central beaches, Son Tra peninsula, and transport links.',
        'destinations/hoi-an-ancient-town-guide' => 'Hoi An: ancient town preservation, boutique bases, tailor etiquette, and crowd avoidance.',
        'destinations/hue-imperial-city-guide' => 'Hue: Nguyen dynasty heritage, imperial citadel, royal tombs, and DMZ excursions.',
        'destinations/ha-long-bay-travel-guide' => 'Ha Long Bay: cruise class selection, route zones, overnight logistics, and weather windows.',
        'destinations/cat-ba-travel-guide' => 'Cat Ba Island: Lan Ha Bay gateway, national park trekking, rock climbing, and ferry routes.',
        'destinations/bai-tu-long-bay-guide' => 'Bai Tu Long Bay: low-crowd alternative to central Ha Long, geological overview, and permits.',
        'destinations/ninh-binh-travel-guide' => 'Ninh Binh: Trang An vs Tam Coc, bicycle routes, karst landscape, and day trip vs overnight.',
        'destinations/tam-coc-travel-guide' => 'Tam Coc: boat tour logistics, viewpoints, Mua Cave, and accommodation pockets.',
        'destinations/sapa-travel-guide' => 'Sapa: mountain trekking, tribal homestays, Fansipan cable car, and seasonal terrace timings.',
        'destinations/ha-giang-loop-planning-guide' => 'Ha Giang Loop: northern frontier motorbike route, road safety, permits, and easy rider selection.',
        'destinations/phong-nha-travel-guide' => 'Phong Nha-Ke Bang: world-class cave expeditions, Son Doong permits, and karst jungle trekking.',
        'destinations/phu-quoc-travel-guide' => 'Phu Quoc: Gulf of Thailand island bases, beach safety, cable car, and dry-season timing.',
        'destinations/con-dao-travel-guide' => 'Con Dao: remote historic archipelago, marine park, turtle nesting, and flight availability.',
        'destinations/nha-trang-travel-guide' => 'Nha Trang: southern coastal resort, diving, island hopping, and family holiday planning.',
        'destinations/quy-nhon-travel-guide' => 'Quy Nhon: unhurried central coastal destination, Ky Co beach, and seafood culture.',
        'destinations/mekong-delta-travel-guide' => 'Mekong Delta: river networks, floating markets, Can Tho, Ben Tre, and sustainable homestays.',
        'destinations/mu-cang-chai-travel-guide' => 'Mu Cang Chai: golden rice terrace season, photography vantage points, and mountain homestays.',
        'destinations/pu-luong-travel-guide' => 'Pu Luong Nature Reserve: peaceful northern valley, water wheels, bamboo rafting, and eco-lodges.',
    ];

    foreach ($destinations as $path => $desc) {
        echo "- [{$desc}]({$site_url}/{$path}/)\n";
    }

    echo "\n## Route Itineraries\n\n";
    $itineraries = [
        'itineraries/7-days-in-vietnam' => '7-Day Express Route: North-South priority balancing Hanoi, Halong Bay, and Hoi An.',
        'itineraries/10-days-in-vietnam' => '10-Day Golden Itinerary: Classic first-timer route with balanced pacing across 3 regions.',
        'itineraries/14-days-in-vietnam' => '14-Day Comprehensive Vietnam Route: Seamless journey connecting mountains, bays, and heritage cities.',
        'itineraries/21-days-in-vietnam' => '21-Day In-Depth Journey: Complete exploration including Ha Giang, Phong Nha, and Mekong Delta.',
        'itineraries/hanoi-in-2-days' => '48 Hours in Hanoi: Curated cultural walking loops, street food trail, and museum scheduling.',
    ];

    foreach ($itineraries as $path => $desc) {
        echo "- [{$desc}]({$site_url}/{$path}/)\n";
    }

    echo "\n## Practical Planning & Preparation\n\n";
    $planning = [
        'plan/best-time-to-visit-vietnam' => 'Weather & Seasons: Regional climate breakdown, monsoon schedules, and harvest months.',
        'plan/vietnam-evisa' => 'Official E-Visa Guide: Direct immigration portal steps, photo specifications, and port entry rules.',
        'plan/transport-within-vietnam' => 'Domestic Transit: Domestic flights, Reunification Express trains, sleeper buses, and private drivers.',
        'plan/money-cash-cards-atms' => 'Currency & Payments: Cash etiquette, ATM withdrawal limits, card fees, and tipping conventions.',
        'plan/sim-esim-vietnam' => 'Connectivity: Airport eSIM setup, 4G network coverage, Viettel/Vinaphone comparison.',
        'plan/safety-scams-vietnam' => 'Safety & Common Scams: Airport taxi fraud, meter tampering, rental bike safety, and emergency contacts.',
    ];

    foreach ($planning as $path => $desc) {
        echo "- [{$desc}]({$site_url}/{$path}/)\n";
    }

    echo "\n## Strategic Comparisons\n\n";
    $comparisons = [
        'compare/ha-long-bay-vs-lan-ha-bay' => 'Ha Long Bay vs Lan Ha Bay: Route crowds, cruise permits, water quality, and cost comparison.',
        'compare/sapa-vs-ha-giang' => 'Sapa vs Ha Giang: Trekking difficulty, cultural encounters, motorbiking vs walking.',
        'compare/da-nang-vs-hoi-an' => 'Da Nang vs Hoi An: Modern beach city amenities vs historic lantern town charm.',
        'compare/hanoi-vs-ho-chi-minh-city' => 'Hanoi vs Ho Chi Minh City: Northern cultural capital vs southern commercial metropolis.',
    ];

    foreach ($comparisons as $path => $desc) {
        echo "- [{$desc}]({$site_url}/{$path}/)\n";
    }

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
        . "LLMs-Txt: {$site_url}/llms.txt\n";

    return rtrim($output) . "\n" . $ai_directives;
}
add_filter('robots_txt', 'vg_add_ai_crawler_directives', 30, 2);
