<?php
if (! defined('ABSPATH')) { exit; }
function vg_home_url(string $path): string { return home_url('/' . trim($path, '/') . '/'); }
function vg_homepage_data(): array
{
    return [
        'navigation' => [
            ['label' => 'Plan', 'url' => vg_home_url('plan')],
            ['label' => 'Destinations', 'url' => vg_home_url('destinations')],
            ['label' => 'Itineraries', 'url' => vg_home_url('itineraries')],
            ['label' => 'Compare', 'url' => vg_home_url('compare')],
            ['label' => 'Costs', 'url' => vg_home_url('costs')],
        ],
        'trip_lengths' => [
            ['label' => '7 days', 'url' => vg_home_url('itineraries/7-days-in-vietnam')],
            ['label' => '10 days', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['label' => '14 days', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['label' => '21 days', 'url' => vg_home_url('itineraries/21-days-in-vietnam')],
        ],
        'travel_styles' => [
            ['label' => 'First trip', 'url' => vg_home_url('plan/vietnam-travel-guide')],
            ['label' => 'Food', 'url' => vg_home_url('destinations/best-things-to-do-in-hoi-an')],
            ['label' => 'Beach', 'url' => vg_home_url('destinations/best-beaches-in-vietnam')],
            ['label' => 'Family', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['label' => 'Premium', 'url' => vg_home_url('destinations/con-dao-travel-guide')],
        ],
        'itineraries' => [
            ['eyebrow' => 'First journey', 'title' => '10 days: north, center, south', 'fit' => 'Best for a first trip that needs one coherent national overview.', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['eyebrow' => 'More breathing room', 'title' => '14 days: Vietnam at a calmer pace', 'fit' => 'Best for travelers who want stronger place depth and fewer rushed transfers.', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['eyebrow' => 'Landscape-led', 'title' => 'North, center, or south', 'fit' => 'Best for choosing one region job before stitching the whole country.', 'url' => vg_home_url('compare/north-central-south-vietnam')],
            ['eyebrow' => 'Island finish', 'title' => 'Con Dao, when the route can slow down', 'fit' => 'Best for couples and families who can protect a quieter island chapter.', 'url' => vg_home_url('destinations/con-dao-travel-guide')],
        ],
        'destinations' => [
            ['title' => 'Hanoi', 'best_for' => 'Food, history, and a first arrival.', 'skip_if' => 'You want a quiet coastal base.', 'url' => vg_home_url('destinations/hanoi-travel-guide')],
            ['title' => 'Hoi An', 'best_for' => 'Walkable evenings, food, and a slower center.', 'skip_if' => 'You need a major-city itinerary.', 'url' => vg_home_url('destinations/best-things-to-do-in-hoi-an')],
            ['title' => 'Ninh Binh', 'best_for' => 'Karst landscapes without an overnight cruise.', 'skip_if' => 'You dislike early starts and rural transfers.', 'url' => vg_home_url('destinations/ninh-binh-travel-guide')],
            ['title' => 'Cat Ba', 'best_for' => 'A bay base with island time and Lan Ha access.', 'skip_if' => 'You only want the iconic Ha Long cruise checklist.', 'url' => vg_home_url('destinations/cat-ba-travel-guide')],
            ['title' => 'Ha Long Bay', 'best_for' => 'The classic northern seascape decision.', 'skip_if' => 'You already know you want a quieter bay or a skip.', 'url' => vg_home_url('destinations/ha-long-bay-travel-guide')],
            ['title' => 'Phu Quoc', 'best_for' => 'An easy beach finish with resort choice.', 'skip_if' => 'You want a culture-first final stop.', 'url' => vg_home_url('destinations/phu-quoc-travel-guide')],
        ],
        'comparisons' => [
            ['title' => 'Ha Long Bay vs Lan Ha Bay', 'verdict' => 'Choose icon value or choose a calmer route.', 'url' => vg_home_url('compare/ha-long-bay-vs-lan-ha-bay')],
            ['title' => 'Da Nang vs Hoi An', 'verdict' => 'Choose airport-and-beach logistics or choose a slower heritage base.', 'url' => vg_home_url('compare/da-nang-vs-hoi-an')],
            ['title' => 'North vs Central vs South', 'verdict' => 'Choose one region job before stitching the whole country.', 'url' => vg_home_url('compare/north-central-south-vietnam')],
        ],
        'essentials' => [
            ['title' => 'Vietnam e-visa', 'meta' => 'Entry rules and common application mistakes.', 'url' => vg_home_url('plan/vietnam-evisa')],
            ['title' => 'Best time to visit', 'meta' => 'Plan around regions, not one national forecast.', 'url' => vg_home_url('plan/best-time-to-visit-vietnam')],
            ['title' => 'Travel cost', 'meta' => 'Realistic budget ranges for different travel styles.', 'url' => vg_home_url('costs/vietnam-travel-cost')],
            ['title' => 'SIM and eSIM', 'meta' => 'Stay connected from arrival without overspending.', 'url' => vg_home_url('plan/sim-esim-vietnam')],
            ['title' => 'Transport', 'meta' => 'Flights, trains, buses, transfers, and local transport.', 'url' => vg_home_url('plan/transport-within-vietnam')],
            ['title' => 'Safety and scams', 'meta' => 'Practical risk management without alarmism.', 'url' => vg_home_url('plan/safety-scams-vietnam')],
        ],
    ];
}
