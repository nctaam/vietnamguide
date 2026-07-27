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
            ['label' => 'First trip', 'url' => vg_home_url('plan/vietnam-for-first-time-visitors')],
            ['label' => 'Food', 'url' => vg_home_url('itineraries/vietnam-food-itinerary')],
            ['label' => 'Beach', 'url' => vg_home_url('itineraries/vietnam-beach-itinerary')],
            ['label' => 'Family', 'url' => vg_home_url('itineraries/vietnam-family-itinerary')],
            ['label' => 'Premium', 'url' => vg_home_url('itineraries/vietnam-luxury-itinerary')],
        ],
        'itineraries' => [
            ['eyebrow' => 'First journey', 'title' => '10 days: north, center, south', 'fit' => 'Best for a first trip that needs one coherent national overview.', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['eyebrow' => 'More breathing room', 'title' => '14 days: Vietnam at a calmer pace', 'fit' => 'Best for travelers who want stronger place depth and fewer rushed transfers.', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['eyebrow' => 'Landscape-led', 'title' => 'Northern Vietnam', 'fit' => 'Best for mountain roads, limestone country, Hanoi, and the bays.', 'url' => vg_home_url('itineraries/northern-vietnam-itinerary')],
            ['eyebrow' => 'Refined comfort', 'title' => 'Premium Vietnam', 'fit' => 'Best for couples and families who want stronger stays and easier logistics.', 'url' => vg_home_url('itineraries/vietnam-luxury-itinerary')],
        ],
        'destinations' => [
            ['title' => 'Hanoi', 'best_for' => 'Food, history, and a confident first arrival.', 'skip_if' => 'You want a quiet coastal base.', 'url' => vg_home_url('destinations/hanoi')],
            ['title' => 'Hoi An', 'best_for' => 'Walkable evenings, food, and a slower center.', 'skip_if' => 'You want a major-city itinerary.', 'url' => vg_home_url('destinations/hoi-an')],
            ['title' => 'Ninh Binh', 'best_for' => 'Karst landscapes without an overnight cruise.', 'skip_if' => 'You dislike early starts and rural transfers.', 'url' => vg_home_url('destinations/ninh-binh')],
            ['title' => 'Lan Ha Bay', 'best_for' => 'A calmer bay experience with Cat Ba access.', 'skip_if' => 'You need the most iconic Ha Long checklist.', 'url' => vg_home_url('destinations/lan-ha-bay')],
            ['title' => 'Ha Giang', 'best_for' => 'High-impact mountain scenery and road journeys.', 'skip_if' => 'You have limited time or dislike long road days.', 'url' => vg_home_url('destinations/ha-giang')],
            ['title' => 'Phu Quoc', 'best_for' => 'An easy beach finish with resort choice.', 'skip_if' => 'You want a culture-first final stop.', 'url' => vg_home_url('destinations/phu-quoc')],
        ],
        'comparisons' => [
            ['title' => 'Ha Long Bay vs Lan Ha Bay', 'verdict' => 'Choose icon value or choose a calmer route.', 'url' => vg_home_url('compare/ha-long-bay-vs-lan-ha-bay')],
            ['title' => 'Sapa vs Ha Giang', 'verdict' => 'Choose easier access or choose the stronger road journey.', 'url' => vg_home_url('compare/sapa-vs-ha-giang')],
            ['title' => 'Hanoi vs Ho Chi Minh City', 'verdict' => 'Choose layered history or choose southern energy.', 'url' => vg_home_url('compare/hanoi-vs-ho-chi-minh-city')],
        ],
        'essentials' => [
            ['title' => 'Vietnam e-visa', 'meta' => 'Entry rules and common application mistakes.', 'url' => vg_home_url('plan/vietnam-evisa')],
            ['title' => 'Best time to visit', 'meta' => 'Plan around regions, not one national forecast.', 'url' => vg_home_url('plan/best-time-to-visit-vietnam')],
            ['title' => 'Travel cost', 'meta' => 'Realistic budget ranges for different travel styles.', 'url' => vg_home_url('costs/vietnam-travel-cost')],
            ['title' => 'SIM and eSIM', 'meta' => 'Stay connected from arrival without overspending.', 'url' => vg_home_url('plan/sim-esim-vietnam')],
            ['title' => 'Getting around', 'meta' => 'Flights, trains, buses, transfers, and local transport.', 'url' => vg_home_url('plan/getting-around-vietnam')],
            ['title' => 'Safety', 'meta' => 'Practical risk management without alarmism.', 'url' => vg_home_url('plan/is-vietnam-safe')],
        ],
    ];
}
