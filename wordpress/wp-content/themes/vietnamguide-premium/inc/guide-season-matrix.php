<?php
/**
 * VietnamGuide Regional Weather & Seasonality Intelligence Matrix Component
 *
 * Provides an interactive, zero-dependency client-side climate matrix, route-fit risk gauge,
 * microclimate elevation calculator, cultural festival radar, and dynamic packing checklist.
 *
 * @package VietnamGuide
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Returns the comprehensive 12-month regional climate and travel dataset.
 *
 * @return array<string, array<string, mixed>>
 */
function vg_get_season_matrix_dataset(): array
{
    return [
        'jan' => [
            'id' => 'jan',
            'name' => 'January',
            'season' => 'winter',
            'season_label' => 'Winter',
            'verdict' => 'Prime month for Southern beaches (Phu Quoc) and dry northern city exploring. Northern mountains (Sa Pa, Ha Giang) are cold and foggy—pack thermal layers. Note Tết travel surges if visiting late January.',
            'north' => [
                'temp_c' => '13–19°C',
                'temp_f' => '55–66°F',
                'status' => 'Cool & Crisp',
                'risk' => 'low',
                'risk_label' => 'Low Risk',
                'swimming' => 'Too cold for swimming (18–19°C)',
                'elevation_offset' => 'Sa Pa & Ha Giang run 8–10°C colder (4–11°C / 39–52°F); night frosts possible.',
                'highlights' => 'Clear dry days in Hanoi and Ninh Binh; misty romantic karsts in Halong Bay.',
            ],
            'central' => [
                'temp_c' => '19–24°C',
                'temp_f' => '66–75°F',
                'status' => 'Mild & Drying Out',
                'risk' => 'moderate',
                'risk_label' => 'Moderate',
                'swimming' => 'Cool water (21°C); rough swells early month',
                'elevation_offset' => 'Hai Van Pass creates a weather wall: Hue can be misty while Da Nang is dry.',
                'highlights' => 'Hoi An lantern alleys comfortable to walk without summer heat; Cham island boats suspended.',
            ],
            'south' => [
                'temp_c' => '22–32°C',
                'temp_f' => '72–90°F',
                'status' => 'Golden Dry Season',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Perfect calm turquoise water (27–28°C)',
                'elevation_offset' => 'Da Lat in central highlands is 14–22°C (sweater weather) compared to sweltering Saigon.',
                'highlights' => 'Phu Quoc west coast crystal clear; Mekong Delta orchards vibrant and accessible.',
            ],
            'radar' => [
                'event' => 'Tết Nguyên Đán (Lunar New Year) Season',
                'type' => 'advisory',
                'note' => 'If Tết falls in late January, book intercity trains and domestic flights 2–3 months ahead. Many local family shops close for 3 days, while major tourist sites remain open.',
            ],
            'checklist' => [
                ['name' => 'Merino wool or fleece thermal base layer', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Crucial for Sa Pa & Ha Giang high passes'],
                ['name' => 'Packable lightweight down jacket', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Compresses easily into daypack for northern nights'],
                ['name' => 'Breathable linen / cotton shirts', 'category' => 'clothing', 'source' => 'buy', 'note' => 'For Saigon and southern island sunshine'],
                ['name' => 'Trail sneakers with wet-traction grip', 'category' => 'footwear', 'source' => 'bring', 'note' => 'Cobblestones and temple steps get slick in morning mist'],
                ['name' => 'Slip-on waterproof sandals', 'category' => 'footwear', 'source' => 'buy', 'note' => 'Easy off/on at temples; cheap to buy locally ($3–$5)'],
                ['name' => 'DEET insect repellent (20–30%)', 'category' => 'health', 'source' => 'bring', 'note' => 'Hard to find high-concentration DEET in rural pharmacies'],
                ['name' => 'Reef-safe broad spectrum sunscreen', 'category' => 'health', 'source' => 'bring', 'note' => 'Essential for Phu Quoc; imported brands expensive in VN'],
                ['name' => 'Electrolyte rehydration salts', 'category' => 'health', 'source' => 'buy', 'note' => 'Available at any pharmacy ("Oresol") for $0.20'],
                ['name' => '10L waterproof dry bag', 'category' => 'gear', 'source' => 'buy', 'note' => 'For boat trips in Halong or Mekong Delta; cheap in Old Quarter'],
                ['name' => 'Universal travel adapter (Type A/C/G)', 'category' => 'gear', 'source' => 'bring', 'note' => 'Most VN outlets take Type C flat/round two-pin plugs'],
            ],
            'routes' => [
                ['title' => '10-Day Classic Route', 'url' => '/itineraries/10-days-in-vietnam/', 'fit' => 'Excellent (Dry North & Sunny South)'],
                ['title' => 'Phu Quoc Travel Guide', 'url' => '/destinations/phu-quoc-travel-guide/', 'fit' => 'Peak Season (Calm seas & beach downtime)'],
                ['title' => 'Hanoi Travel Guide', 'url' => '/destinations/hanoi-travel-guide/', 'fit' => 'Crisp walking weather; street food season'],
            ],
        ],
        'feb' => [
            'id' => 'feb',
            'name' => 'February',
            'season' => 'winter',
            'season_label' => 'Late Winter / Early Spring',
            'verdict' => 'One of the best cross-country months of the year. Minimal rainfall nationwide, comfortable temperatures in Hanoi, dry beaches beginning in Central Vietnam, and peak island weather in the South.',
            'north' => [
                'temp_c' => '15–21°C',
                'temp_f' => '59–70°F',
                'status' => 'Pleasant & Cool',
                'risk' => 'low',
                'risk_label' => 'Prime',
                'swimming' => 'Cool (19–20°C); short dips only',
                'elevation_offset' => 'Sa Pa peach & plum blossoms bloom; mountain nights 7–12°C.',
                'highlights' => 'Great visibility in Ninh Binh river valleys; comfortable trekking weather.',
            ],
            'central' => [
                'temp_c' => '21–26°C',
                'temp_f' => '70–79°F',
                'status' => 'Dry & Sunny',
                'risk' => 'low',
                'risk_label' => 'Prime',
                'swimming' => 'Comfortable (23–24°C); An Bang beach opens',
                'elevation_offset' => 'Bana Hills & Hai Van Pass have clear vistas with zero typhoon threats.',
                'highlights' => 'Hue imperial citadel dry and walkable; Hoi An ancient town lantern festival pristine.',
            ],
            'south' => [
                'temp_c' => '23–33°C',
                'temp_f' => '73–91°F',
                'status' => 'Sunny & Warm',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Ideal (28°C); crystal clear snorkeling',
                'elevation_offset' => 'Mekong breeze keeps river cruises cooler than downtown Saigon.',
                'highlights' => 'Peak dry season across Phu Quoc, Con Dao, and the Mekong Delta waterways.',
            ],
            'radar' => [
                'event' => 'Spring Temple Festivals & Flower Seasons',
                'type' => 'highlight',
                'note' => 'Post-Tết pilgrimage festivals across northern temples (Perfume Pagoda, Bai Dinh). Vibrant cultural atmosphere without disruptive business closures.',
            ],
            'checklist' => [
                ['name' => 'Light windbreaker or denim jacket', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Perfect for evening Hanoi scooter food tours'],
                ['name' => 'Merino wool socks & trail sneakers', 'category' => 'footwear', 'source' => 'bring', 'note' => 'For walking ancient temple grounds and citadel paths'],
                ['name' => 'Linen shirts & lightweight trousers', 'category' => 'clothing', 'source' => 'buy', 'note' => 'Comfortable for temples requiring knee/shoulder coverage'],
                ['name' => 'Swimwear & UV rash guard', 'category' => 'clothing', 'source' => 'bring', 'note' => 'For Central and Southern beaches'],
                ['name' => 'Polarized sunglasses & sun hat', 'category' => 'health', 'source' => 'buy', 'note' => 'Conical leaf hats ("nón lá") provide unmatched shade for $2'],
                ['name' => 'High-SPF reef-safe sunscreen', 'category' => 'health', 'source' => 'bring', 'note' => 'Crucial for day cruises and beach days'],
                ['name' => 'Pocket hand sanitizer & tissues', 'category' => 'health', 'source' => 'buy', 'note' => 'Handy for remote train stations and street food stalls'],
                ['name' => 'Compact power bank (10,000–20,000 mAh)', 'category' => 'gear', 'source' => 'bring', 'note' => 'Must be in carry-on bag for domestic airport security'],
            ],
            'routes' => [
                ['title' => '14-Day Balanced Route', 'url' => '/itineraries/14-days-in-vietnam/', 'fit' => 'Unbeatable window (North to South all green)'],
                ['title' => 'Ninh Binh Travel Guide', 'url' => '/destinations/ninh-binh-travel-guide/', 'fit' => 'Clear water reflections on Tam Coc sampans'],
                ['title' => 'Da Nang vs Hoi An Guide', 'url' => '/compare/da-nang-vs-hoi-an/', 'fit' => 'Dry central coast chapter ready to book'],
            ],
        ],
        'mar' => [
            'id' => 'mar',
            'name' => 'March',
            'season' => 'spring',
            'season_label' => 'Spring',
            'verdict' => 'The unanimous gold medal month for grand cross-country journeys. Calm seas in Halong Bay, warm turquoise water in Da Nang/Hoi An, and dry sunny days across Saigon and the islands.',
            'north' => [
                'temp_c' => '18–25°C',
                'temp_f' => '64–77°F',
                'status' => 'Warm & Clear',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Pleasant (22–24°C); kayaking ideal',
                'elevation_offset' => 'Sa Pa trekking conditions at annual peak: dry trails, cool morning air.',
                'highlights' => 'Superb visibility on Halong / Lan Ha Bay overnight cruises.',
            ],
            'central' => [
                'temp_c' => '23–29°C',
                'temp_f' => '73–84°F',
                'status' => 'Dry & Warm',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Warm & calm (25°C); great snorkeling',
                'elevation_offset' => 'Phong Nha cave rivers low, clear, and safe for swimming/caving.',
                'highlights' => 'An Bang and My Khe beaches boast glassy calm water; Hoi An cycling is sublime.',
            ],
            'south' => [
                'temp_c' => '24–34°C',
                'temp_f' => '75–93°F',
                'status' => 'Sunny & Warm',
                'risk' => 'low',
                'risk_label' => 'Prime',
                'swimming' => 'Ideal (28–29°C)',
                'elevation_offset' => 'Central Highlands coffee flowers blanket Da Lat & Buon Ma Thuot in white.',
                'highlights' => 'Last full dry month before southern green season; Phu Quoc sunsets unobstructed.',
            ],
            'radar' => [
                'event' => 'Hội An Lantern Festival & Coffee Blossom Season',
                'type' => 'highlight',
                'note' => 'Peak month for photographers. Zero typhoon risk, clear starry skies on bay cruises, and lively street cafe culture.',
            ],
            'checklist' => [
                ['name' => 'Breathable technical hiking shoes', 'category' => 'footwear', 'source' => 'bring', 'note' => 'For Sa Pa terraces and Phong Nha national park'],
                ['name' => 'Lightweight linen shirts & day dresses', 'category' => 'clothing', 'source' => 'buy', 'note' => 'Custom tailoring in Hoi An takes 24–48 hours'],
                ['name' => 'Reef-friendly mineral sunscreen (SPF 50+)', 'category' => 'health', 'source' => 'bring', 'note' => 'Protects coral reefs in Cham Islands & Phu Quoc'],
                ['name' => 'Quick-dry microfiber travel towel', 'category' => 'gear', 'source' => 'bring', 'note' => 'For spontaneous waterfall and beach dips'],
                ['name' => 'Waterproof phone lanyard case', 'category' => 'gear', 'source' => 'buy', 'note' => 'Essential on Trang An sampan boat tours ($2 locally)'],
                ['name' => 'Electrolyte replenishment drink mix', 'category' => 'health', 'source' => 'bring', 'note' => 'Keeps energy high during warm afternoon temple strolls'],
            ],
            'routes' => [
                ['title' => '21-Day Grand Vietnam Route', 'url' => '/itineraries/21-days-in-vietnam/', 'fit' => 'Best month of the year for full 3-week trip'],
                ['title' => 'Ha Long Bay Cruise Guide', 'url' => '/destinations/ha-long-bay-travel-guide/', 'fit' => 'Crisp blue sky, calm anchorages, zero fog'],
                ['title' => 'Sapa Travel Guide', 'url' => '/destinations/sapa-travel-guide/', 'fit' => 'Dry mountain trails and optimal visibility'],
            ],
        ],
        'apr' => [
            'id' => 'apr',
            'name' => 'April',
            'season' => 'spring',
            'season_label' => 'Late Spring',
            'verdict' => 'Outstanding travel weather with warm sunshine throughout. Central coast beaches are prime; North transitions to early summer warmth; South is hot with early occasional afternoon cloud cover.',
            'north' => [
                'temp_c' => '22–29°C',
                'temp_f' => '72–84°F',
                'status' => 'Warm & Sunny',
                'risk' => 'low',
                'risk_label' => 'Prime',
                'swimming' => 'Great (24–26°C); swimming in Lan Ha Bay prime',
                'elevation_offset' => 'Sa Pa warms to 16–23°C; valleys turn bright emerald green.',
                'highlights' => 'Hanoi cafe terraces buzzing; Ninh Binh lotus ponds begin budding.',
            ],
            'central' => [
                'temp_c' => '24–32°C',
                'temp_f' => '75–90°F',
                'status' => 'Sunny & Warm',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Ideal (26°C); calm sea, Cham island ferries running',
                'elevation_offset' => 'Hue citadel afternoon heat rising; do imperial tombs in the early morning.',
                'highlights' => 'Peak beach month for Da Nang, Hoi An, and Quy Nhon mainland coast.',
            ],
            'south' => [
                'temp_c' => '25–35°C',
                'temp_f' => '77–95°F',
                'status' => 'Hot & Dry to Humid',
                'risk' => 'moderate',
                'risk_label' => 'Good (Warm)',
                'swimming' => 'Very warm (29°C)',
                'elevation_offset' => 'Escape Saigon midday heat by booking riverfront or rooftop retreats.',
                'highlights' => 'Con Dao sea turtles begin nesting season; island diving visibility peak.',
            ],
            'radar' => [
                'event' => 'Reunification Day (Apr 30) & Hue Festival',
                'type' => 'advisory',
                'note' => 'April 30 – May 1 is a major national public holiday. Beach resorts in Da Nang and Phu Quoc book up quickly; reserve domestic flights early.',
            ],
            'checklist' => [
                ['name' => 'Wide-brim sun protection hat', 'category' => 'clothing', 'source' => 'buy', 'note' => 'Lightweight woven straw hats widely available'],
                ['name' => 'High-UPF sun protective long-sleeve shirt', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Saves skin on open kayak and motorcycle trips'],
                ['name' => 'DEET insect spray & after-bite cream', 'category' => 'health', 'source' => 'bring', 'note' => 'Keep handy for twilight river walks'],
                ['name' => 'Waterproof dry bag (15L–20L)', 'category' => 'gear', 'source' => 'buy', 'note' => 'Keeps electronics safe on island speedboats'],
                ['name' => 'Refillable insulated water bottle', 'category' => 'gear', 'source' => 'bring', 'note' => 'Hotels provide free filtered water refill stations'],
            ],
            'routes' => [
                ['title' => 'Best Beaches in Vietnam', 'url' => '/destinations/best-beaches-in-vietnam/', 'fit' => 'Mainland and island coastlines all sparkling'],
                ['title' => 'Con Dao Travel Guide', 'url' => '/destinations/con-dao-travel-guide/', 'fit' => 'Calm seas for boat excursions & diving'],
                ['title' => 'Da Nang Travel Guide', 'url' => '/destinations/da-nang-travel-guide/', 'fit' => 'Prime beach & Ba Na Hills weather'],
            ],
        ],
        'may' => [
            'id' => 'may',
            'name' => 'May',
            'season' => 'summer',
            'season_label' => 'Early Summer',
            'verdict' => 'Central Vietnam (Da Nang, Hoi An, Nha Trang, Quy Nhon) enters its golden beach peak. The North and South see early summer showers (typically 30–45 mins in late afternoon) that leave air fresh.',
            'north' => [
                'temp_c' => '25–33°C',
                'temp_f' => '77–91°F',
                'status' => 'Warm with Showers',
                'risk' => 'moderate',
                'risk_label' => 'Good',
                'swimming' => 'Warm & pleasant (26–27°C)',
                'elevation_offset' => 'Water pouring season ("mùa nước đổ") turns northern terraces into giant mirrors.',
                'highlights' => 'Spectacular reflection photography in Mu Cang Chai and Sa Pa terraces.',
            ],
            'central' => [
                'temp_c' => '26–34°C',
                'temp_f' => '79–93°F',
                'status' => 'Sunny Beach Weather',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Optimal (27°C); crystal clear diving',
                'elevation_offset' => 'Breezy coastlines make 33°C feel much more comfortable than inland.',
                'highlights' => 'Unbeatable beach conditions along Da Nang, Cham Islands, and Quy Nhon.',
            ],
            'south' => [
                'temp_c' => '25–34°C',
                'temp_f' => '77–93°F',
                'status' => 'Green Season Begins',
                'risk' => 'moderate',
                'risk_label' => 'Manageable',
                'swimming' => 'Warm (29°C); afternoon swells occasional',
                'elevation_offset' => 'Afternoon downpours are brief and predictable (usually around 3:30–5:00 PM).',
                'highlights' => 'Tropical fruits (mango, rambutan, mangosteen) flood southern markets.',
            ],
            'radar' => [
                'event' => 'Water Pouring Season in Northern Terraces',
                'type' => 'highlight',
                'note' => 'Farmers flood hillside terraces for plowing. Photographers travel worldwide to capture the silver-water reflections under sunset clouds.',
            ],
            'checklist' => [
                ['name' => 'Compact umbrella or ultralight rain poncho', 'category' => 'gear', 'source' => 'buy', 'note' => 'Convenient $1 disposable ponchos sold on every street'],
                ['name' => 'Breathable quick-dry athletic shorts', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Dries within an hour after sudden tropical rains'],
                ['name' => 'Water-resistant walking sandals (Teva/Chaco)', 'category' => 'footwear', 'source' => 'bring', 'note' => 'Perfect for puddles and wet boat docks'],
                ['name' => 'Reef-safe sunscreen & aloe vera gel', 'category' => 'health', 'source' => 'bring', 'note' => 'Strong central coast UV index'],
                ['name' => 'Anti-mosquito spray & bite relief stick', 'category' => 'health', 'source' => 'bring', 'note' => 'Rising humidity brings out twilight mosquitoes'],
            ],
            'routes' => [
                ['title' => 'Quy Nhon Travel Guide', 'url' => '/destinations/quy-nhon-travel-guide/', 'fit' => 'Quiet mainland beach season at its absolute best'],
                ['title' => 'Ha Giang Loop Guide', 'url' => '/destinations/ha-giang-loop-planning-guide/', 'fit' => 'Water pouring mirror season across stone plateaus'],
                ['title' => 'Mekong Delta Travel Guide', 'url' => '/destinations/mekong-delta-travel-guide/', 'fit' => 'Fruit harvest season begins; morning boat tours dry'],
            ],
        ],
        'jun' => [
            'id' => 'jun',
            'name' => 'June',
            'season' => 'summer',
            'season_label' => 'Summer',
            'verdict' => 'Central Vietnam coast is the clear hero region (dry, sunny, calm seas). The North and South experience tropical summer heat and afternoon downpours; schedule temple tours in early mornings.',
            'north' => [
                'temp_c' => '27–34°C',
                'temp_f' => '81–93°F',
                'status' => 'Hot & Humid, Rain Intervals',
                'risk' => 'moderate',
                'risk_label' => 'Moderate',
                'swimming' => 'Very warm (28–29°C)',
                'elevation_offset' => 'Sa Pa and Ha Giang offer a cool retreat from lowland heat (19–25°C).',
                'highlights' => 'Lush green valleys; early morning walks in Hanoi Old Quarter peaceful before heat.',
            ],
            'central' => [
                'temp_c' => '27–35°C',
                'temp_f' => '81–95°F',
                'status' => 'Dry, Hot & Sunny',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Peak beach swimming (28°C); calm bays',
                'elevation_offset' => 'Sea breezes make beaches pleasant; afternoons hot inland in Hue citadel.',
                'highlights' => 'Glassy sea conditions in Da Nang, Hoi An, and Cham Islands.',
            ],
            'south' => [
                'temp_c' => '25–33°C',
                'temp_f' => '77–91°F',
                'status' => 'Green Season Rains',
                'risk' => 'moderate',
                'risk_label' => 'Manageable',
                'swimming' => 'Warm (29°C); west coast Phu Quoc has waves',
                'elevation_offset' => 'Switch from west coast Phu Quoc to east coast (Sao Beach) for flat calm waters.',
                'highlights' => 'Tropical fruit orchards in Ben Tre and Can Tho in full abundance.',
            ],
            'radar' => [
                'event' => 'Da Nang International Fireworks Festival (DIFF)',
                'type' => 'highlight',
                'note' => 'Weekend fireworks competitions illuminate the Han River throughout June and July. Book Da Nang riverside hotels 4–6 weeks early.',
            ],
            'checklist' => [
                ['name' => 'Moisture-wicking athletic tops', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Cotton holds sweat; technical fabrics keep cool'],
                ['name' => 'Heavy-duty dry bag (15L–20L)', 'category' => 'gear', 'source' => 'buy', 'note' => 'Protects electronics during sudden afternoon deluges'],
                ['name' => 'Wet-traction sandals with ankle straps', 'category' => 'footwear', 'source' => 'bring', 'note' => 'Safer than flip-flops on wet tile sidewalks'],
                ['name' => 'Electrolyte rehydration powder', 'category' => 'health', 'source' => 'bring', 'note' => 'Essential for daily hydration in 34°C heat'],
                ['name' => 'UV protective sunglasses & broad hat', 'category' => 'clothing', 'source' => 'buy', 'note' => 'Protects against intense central coast glare'],
            ],
            'routes' => [
                ['title' => 'Da Nang Travel Guide', 'url' => '/destinations/da-nang-travel-guide/', 'fit' => 'Peak fireworks & beach season'],
                ['title' => 'Cham Islands Travel Guide', 'url' => '/destinations/cham-islands-travel-guide/', 'fit' => 'Calm water, prime diving & snorkeling visibility'],
                ['title' => 'Hoi An Travel Guide', 'url' => '/destinations/hoi-an-travel-guide/', 'fit' => 'Morning bike rides and afternoon beach lounging'],
            ],
        ],
        'jul' => [
            'id' => 'jul',
            'name' => 'July',
            'season' => 'summer',
            'season_label' => 'Peak Summer',
            'verdict' => 'Continue prioritizing Central Vietnam coast for guaranteed sun and calm seas. North experiences summer monsoon rains with occasional tropical depressions affecting Halong Bay cruise sailings.',
            'north' => [
                'temp_c' => '27–34°C',
                'temp_f' => '81–93°F',
                'status' => 'Warm, Humid & Storm Risk',
                'risk' => 'moderate',
                'risk_label' => 'Moderate (Storms)',
                'swimming' => 'Very warm (29°C)',
                'elevation_offset' => 'Heavy mountain downpours can cause localized road slips on Ha Giang loop.',
                'highlights' => 'Halong Bay dramatic towering clouds; keep a 1-day buffer for boat sailing permits.',
            ],
            'central' => [
                'temp_c' => '26–34°C',
                'temp_f' => '79–93°F',
                'status' => 'Dry & Sunny Beaches',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Ideal (28°C); perfect swimming',
                'elevation_offset' => 'Hue experiences hot southwest Foehn winds; plan outdoor sites before 10 AM.',
                'highlights' => 'Mainland coast (My Khe, An Bang, Ky Co) sparkling blue with zero storm risk.',
            ],
            'south' => [
                'temp_c' => '25–32°C',
                'temp_f' => '77–90°F',
                'status' => 'Green Season',
                'risk' => 'moderate',
                'risk_label' => 'Manageable',
                'swimming' => 'Warm; west coast choppy waves',
                'elevation_offset' => 'Sao Beach and Khem Beach on Phu Quoc east coast remain sheltered and calm.',
                'highlights' => 'Rain refreshes the city; air quality noticeably clean in Saigon and delta.',
            ],
            'radar' => [
                'event' => 'Summer Coast Festivals & Harbor Master Advisory',
                'type' => 'advisory',
                'note' => 'Halong Bay Port Authority occasionally halts overnight cruises for 24–48 hours if a summer tropical storm nears. Always book cruises with flexible cancellation/reschedule policies.',
            ],
            'checklist' => [
                ['name' => 'Light packable rain shell with pit-zips', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Ventilation is essential in tropical humid downpours'],
                ['name' => 'Fast-drying swim shorts & boardshorts', 'category' => 'clothing', 'source' => 'buy', 'note' => 'Cheap beachwear abundant in Hoi An and Da Nang ($4–$8)'],
                ['name' => 'Waterproof phone pouch with clear screen', 'category' => 'gear', 'source' => 'buy', 'note' => 'Allows photography on boat trips in the rain'],
                ['name' => 'Anti-chafe stick / body glide', 'category' => 'health', 'source' => 'bring', 'note' => 'Saves thighs during hot humid walking tours'],
                ['name' => 'Mosquito repellent with Picaridin or DEET', 'category' => 'health', 'source' => 'bring', 'note' => 'Essential for evening outdoor dining'],
            ],
            'routes' => [
                ['title' => 'Da Nang vs Hoi An Guide', 'url' => '/compare/da-nang-vs-hoi-an/', 'fit' => 'Central coast anchors the summer itinerary'],
                ['title' => 'Ha Long Cruise Questions', 'url' => '/destinations/ha-long-bay-travel-guide/', 'fit' => 'Check cancellation rules and weather buffer margins'],
                ['title' => 'Nha Trang Travel Guide', 'url' => '/destinations/nha-trang-travel-guide/', 'fit' => 'Sunny mainland beaches and calm island boat tours'],
            ],
        ],
        'aug' => [
            'id' => 'aug',
            'name' => 'August',
            'season' => 'summer',
            'season_label' => 'Late Summer',
            'verdict' => 'Final prime beach weeks for Central Vietnam before autumn rains arrive. Northern rice terraces begin turning golden; rains in the North and South start tapering towards month-end.',
            'north' => [
                'temp_c' => '26–33°C',
                'temp_f' => '79–91°F',
                'status' => 'Warm, Rains Tapering',
                'risk' => 'moderate',
                'risk_label' => 'Moderate',
                'swimming' => 'Warm (28°C)',
                'elevation_offset' => 'Sa Pa terraces start yellowing; crisp mountain morning breezes return.',
                'highlights' => 'Mu Cang Chai and Sa Pa terraces transition into late-summer golden hues.',
            ],
            'central' => [
                'temp_c' => '26–34°C',
                'temp_f' => '79–93°F',
                'status' => 'Sunny, Late Rain Possible',
                'risk' => 'low',
                'risk_label' => 'Good',
                'swimming' => 'Very warm (28–29°C); swimming great',
                'elevation_offset' => 'Late August sees transition showers; humidity increases slightly.',
                'highlights' => 'Last high-sun beach weeks in Da Nang and Hoi An before autumn monsoon.',
            ],
            'south' => [
                'temp_c' => '25–32°C',
                'temp_f' => '77–90°F',
                'status' => 'Green Season Showers',
                'risk' => 'moderate',
                'risk_label' => 'Manageable',
                'swimming' => 'Warm; west coast swells',
                'elevation_offset' => 'High water season ("mùa nước nổi") begins in the outer Mekong Delta.',
                'highlights' => 'Floating markets lively with seasonal harvests; lush green countryside.',
            ],
            'radar' => [
                'event' => 'Vu Lan (Ghost Festival) & Autumn Prep',
                'type' => 'highlight',
                'note' => 'Seventh lunar month features colorful Vu Lan ceremonies in Hue and Hoi An pagodas, with floating river lanterns honoring ancestors.',
            ],
            'checklist' => [
                ['name' => 'Waterproof backpack with rain cover', 'category' => 'gear', 'source' => 'bring', 'note' => 'Protects laptop and camera during transit days'],
                ['name' => 'Quick-drying slip-on shoes', 'category' => 'footwear', 'source' => 'bring', 'note' => 'Easy to remove when entering temples and homestays'],
                ['name' => 'Light linen long trousers', 'category' => 'clothing', 'source' => 'buy', 'note' => 'Protects against mosquitoes while staying cool'],
                ['name' => 'Motion sickness pills (Dimenhydrinate)', 'category' => 'health', 'source' => 'buy', 'note' => 'Cheap at local pharmacies ("Nautamine") for $1.50'],
                ['name' => 'Compact micro-fiber travel cloth', 'category' => 'gear', 'source' => 'bring', 'note' => 'For wiping camera lenses and phone screens'],
            ],
            'routes' => [
                ['title' => 'Sapa vs Ha Giang Guide', 'url' => '/compare/sapa-vs-ha-giang/', 'fit' => 'Golden terrace preview season begins in late August'],
                ['title' => 'Hoi An Travel Guide', 'url' => '/destinations/hoi-an-travel-guide/', 'fit' => 'Final dry beach and cycling weeks before autumn'],
                ['title' => 'Mekong Delta Overnight Guide', 'url' => '/destinations/mekong-delta-travel-guide/', 'fit' => 'High water seasonal canals and floating markets'],
            ],
        ],
        'sep' => [
            'id' => 'sep',
            'name' => 'September',
            'season' => 'autumn',
            'season_label' => 'Early Autumn',
            'verdict' => 'Photographer paradise in Northern Vietnam as terraced rice fields turn brilliant gold for the harvest. Central Vietnam transitions into its rainy season; southern rains begin reducing.',
            'north' => [
                'temp_c' => '24–31°C',
                'temp_f' => '75–88°F',
                'status' => 'Golden Harvest, Pleasant',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Pleasant (26°C)',
                'elevation_offset' => 'Sa Pa and Mu Cang Chai golden rice fields at peak glory; cool, dry hiking air.',
                'highlights' => 'Hanoi autumn weather arrives with scented milk flowers and pleasant breezes.',
            ],
            'central' => [
                'temp_c' => '24–31°C',
                'temp_f' => '75–88°F',
                'status' => 'Rain Season Begins',
                'risk' => 'moderate',
                'risk_label' => 'Caution',
                'swimming' => 'Choppy water; red flags possible late month',
                'elevation_offset' => 'Hue receives frequent rains; indoor royal museums and tombs best.',
                'highlights' => 'Fewer tourists in Hoi An; moody atmospheric photography in ancient alleys.',
            ],
            'south' => [
                'temp_c' => '24–31°C',
                'temp_f' => '75–88°F',
                'status' => 'Showers Diminishing',
                'risk' => 'moderate',
                'risk_label' => 'Good',
                'swimming' => 'Warm (28°C)',
                'elevation_offset' => 'Mekong high-water season ("mùa nước nổi") in full bloom at Tra Su cajuput forest.',
                'highlights' => 'Lush wetlands and migratory waterbirds flock to southern delta sanctuaries.',
            ],
            'radar' => [
                'event' => 'Tết Trung Thu (Mid-Autumn Festival) & Harvest',
                'type' => 'highlight',
                'note' => 'Lion dances, mooncakes, and thousands of red lanterns fill Hanoi Old Quarter (Hang Ma street) and Hoi An alleys. National Day is September 2.',
            ],
            'checklist' => [
                ['name' => 'Sturdy hiking boots with muddy grip', 'category' => 'footwear', 'source' => 'bring', 'note' => 'Terrace ridges can be slick during harvest treks'],
                ['name' => 'Camera with extra memory cards & batteries', 'category' => 'gear', 'source' => 'bring', 'note' => 'Golden terrace vistas consume battery quickly'],
                ['name' => 'Light sweater or cardigan for evenings', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Hanoi and Sa Pa evenings turn pleasantly cool'],
                ['name' => 'Compact umbrella & waterproof jacket', 'category' => 'gear', 'source' => 'buy', 'note' => 'For passing showers in Central Vietnam'],
                ['name' => 'DEET insect spray', 'category' => 'health', 'source' => 'bring', 'note' => 'Essential in wetland sanctuaries and rice valleys'],
            ],
            'routes' => [
                ['title' => 'Sapa Travel Guide', 'url' => '/destinations/sapa-travel-guide/', 'fit' => 'Golden terraced rice harvest window'],
                ['title' => 'Hanoi Travel Guide', 'url' => '/destinations/hanoi-travel-guide/', 'fit' => 'Hanoi legendary autumn weather arrives'],
                ['title' => 'Ha Giang Loop Guide', 'url' => '/destinations/ha-giang-loop-planning-guide/', 'fit' => 'Dry stone roads, sweeping yellow valleys'],
            ],
        ],
        'oct' => [
            'id' => 'oct',
            'name' => 'October',
            'season' => 'autumn',
            'season_label' => 'Mid Autumn',
            'verdict' => 'Sublime weather for Hanoi, Halong Bay, and Ha Giang. CRITICAL CAUTION for Central Vietnam: peak monsoon & flood risk in Hoi An and Hue. Pivot beach time to Phu Quoc.',
            'north' => [
                'temp_c' => '21–28°C',
                'temp_f' => '70–82°F',
                'status' => 'Dry, Sunny & Cool',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Cool (23°C); Halong kayaking pristine',
                'elevation_offset' => 'Ha Giang buckwheat flowers bloom in pink and purple across mountain passes.',
                'highlights' => 'Clear blue skies over Halong Bay with zero fog and minimal rainfall.',
            ],
            'central' => [
                'temp_c' => '22–27°C',
                'temp_f' => '72–81°F',
                'status' => 'HIGH FLOOD & RAIN RISK',
                'risk' => 'high',
                'risk_label' => 'HIGH RISK (Floods)',
                'swimming' => 'Dangerous swells; beaches closed; Cham boats halted',
                'elevation_offset' => 'Thu Bon river in Hoi An frequently overflows streets near riverside.',
                'highlights' => 'Plan flexible cancellation; book hotels on higher ground; consider swapping beach days to South.',
            ],
            'south' => [
                'temp_c' => '24–31°C',
                'temp_f' => '75–88°F',
                'status' => 'Dry Season Approaching',
                'risk' => 'low',
                'risk_label' => 'Good (Improving)',
                'swimming' => 'Calm seas returning (28°C)',
                'elevation_offset' => 'Phu Quoc west coast begins clearing up toward late October.',
                'highlights' => 'Great time for Mekong Delta boat explorations; Saigon rains wrap up.',
            ],
            'radar' => [
                'event' => 'Central Vietnam Flood Caution & Buckwheat Festival',
                'type' => 'advisory',
                'note' => 'Central Vietnam experiences 500–800mm of rain in October. Hoi An riverside homestays can flood up to 1 meter. Stay outside the lowest old quarter flood zone or focus on North & South.',
            ],
            'checklist' => [
                ['name' => 'Waterproof dry bag (20L) & rain cover', 'category' => 'gear', 'source' => 'bring', 'note' => 'Critical if transiting through Central Vietnam'],
                ['name' => 'Comfortable water-resistant walking shoes', 'category' => 'footwear', 'source' => 'bring', 'note' => 'Non-slip soles for wet limestone steps in Ninh Binh'],
                ['name' => 'Light fleece or insulated windbreaker', 'category' => 'clothing', 'source' => 'bring', 'note' => 'For chilly evening winds on Ha Giang mountain passes'],
                ['name' => 'Antiseptic wipes & waterproof band-aids', 'category' => 'health', 'source' => 'buy', 'note' => 'Keep cuts clean in wet tropical environments'],
                ['name' => 'Power bank with waterproof sleeve', 'category' => 'gear', 'source' => 'bring', 'note' => 'Carry on board; preserves phone charge on long loop days'],
            ],
            'routes' => [
                ['title' => 'Ha Giang Loop Guide', 'url' => '/destinations/ha-giang-loop-planning-guide/', 'fit' => 'Buckwheat flower bloom & dry road conditions'],
                ['title' => 'Ha Long Bay Cruise Guide', 'url' => '/destinations/ha-long-bay-travel-guide/', 'fit' => 'Peak autumn clarity and calm emerald bays'],
                ['title' => 'Vietnam Rainy Season Route Guide', 'url' => '/plan/best-time-to-visit-vietnam/', 'fit' => 'How to route around Central Vietnam flood season'],
            ],
        ],
        'nov' => [
            'id' => 'nov',
            'name' => 'November',
            'season' => 'autumn',
            'season_label' => 'Late Autumn / Early Winter',
            'verdict' => 'Phenomenal month for Northern Vietnam and Southern Islands. Phu Quoc and Con Dao enter their dry gold season; Hanoi is dry and cool; Central coast rains begin subsiding late month.',
            'north' => [
                'temp_c' => '18–25°C',
                'temp_f' => '64–77°F',
                'status' => 'Dry, Crisp & Sunny',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Cool (21°C); swimming bracing',
                'elevation_offset' => 'Sa Pa enters dry winter season; night temperatures drop to 8–12°C.',
                'highlights' => 'Superb trekking conditions with minimal mud and crisp mountain horizons.',
            ],
            'central' => [
                'temp_c' => '21–26°C',
                'temp_f' => '70–79°F',
                'status' => 'Rains Easing Late Month',
                'risk' => 'moderate',
                'risk_label' => 'Moderate',
                'swimming' => 'Rough seas early month; clears late Nov',
                'elevation_offset' => 'Hue remains wetter than Da Nang; check river water levels before booking.',
                'highlights' => 'Hoi An recovers into cooler pleasant weather by late November.',
            ],
            'south' => [
                'temp_c' => '23–31°C',
                'temp_f' => '73–88°F',
                'status' => 'Dry Season Commences',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Warm, flat & transparent (28°C)',
                'elevation_offset' => 'Phu Quoc west coast beaches (Long Beach, Ong Lang) become mirror-calm.',
                'highlights' => 'Best island resort weather of the year begins across the South.',
            ],
            'radar' => [
                'event' => 'Ok Om Bok Festival & Buckwheat Blooms',
                'type' => 'highlight',
                'note' => 'Khmer Moon-Worship Festival in the Mekong Delta (Soc Trang/Tra Vinh) with thrilling traditional ghe ngo boat races. Buckwheat flower peaks in Ha Giang.',
            ],
            'checklist' => [
                ['name' => 'Medium-weight fleece or warm pullover', 'category' => 'clothing', 'source' => 'bring', 'note' => 'For cool Hanoi evenings and overnight trains'],
                ['name' => 'Swimwear, snorkel mask & flip flops', 'category' => 'clothing', 'source' => 'buy', 'note' => 'For Phu Quoc island dry season'],
                ['name' => 'Sturdy walking shoes with dry traction', 'category' => 'footwear', 'source' => 'bring', 'note' => 'For stone staircases in Tam Coc and Marble Mountains'],
                ['name' => 'High-SPF reef-safe sunscreen', 'category' => 'health', 'source' => 'bring', 'note' => 'UV index rising in the South'],
                ['name' => 'Universal plug adapter & USB-C cables', 'category' => 'gear', 'source' => 'bring', 'note' => 'Reliable charging on sleeper buses/trains'],
            ],
            'routes' => [
                ['title' => '10-Day Classic Route', 'url' => '/itineraries/10-days-in-vietnam/', 'fit' => 'High season begins; excellent conditions North & South'],
                ['title' => 'Phu Quoc Travel Guide', 'url' => '/destinations/phu-quoc-travel-guide/', 'fit' => 'Gold season starts: flat turquoise sea'],
                ['title' => 'Ninh Binh Travel Guide', 'url' => '/destinations/ninh-binh-travel-guide/', 'fit' => 'Dry cool boat trips through karst water caves'],
            ],
        ],
        'dec' => [
            'id' => 'dec',
            'name' => 'December',
            'season' => 'winter',
            'season_label' => 'Winter',
            'verdict' => 'Festive holiday peak: dry, sunny, and warm in Southern Vietnam & Phu Quoc; crisp, cool winter in Hanoi; cold mountain mist in Sa Pa. Pack dual wardrobes if traveling cross-country.',
            'north' => [
                'temp_c' => '14–20°C',
                'temp_f' => '57–68°F',
                'status' => 'Cool Winter, Occasional Fog',
                'risk' => 'low',
                'risk_label' => 'Good (Chilly)',
                'swimming' => 'Too cold for swimming (18°C)',
                'elevation_offset' => 'Sa Pa can dip to 3–7°C (37–45°F); snow/frost occasional on Fansipan peak.',
                'highlights' => 'Festive lights around Hoan Kiem lake and St. Joseph Cathedral; piping hot phở weather.',
            ],
            'central' => [
                'temp_c' => '19–24°C',
                'temp_f' => '66–75°F',
                'status' => 'Cool, Overcast, Drying Out',
                'risk' => 'moderate',
                'risk_label' => 'Moderate',
                'swimming' => 'Cool water (21°C); beach lounging rather than swimming',
                'elevation_offset' => 'Da Nang beaches breezy; pleasant for coastal cycling and street walks.',
                'highlights' => 'Hoi An ancient streets illuminated with holiday lanterns; mild walking weather.',
            ],
            'south' => [
                'temp_c' => '22–31°C',
                'temp_f' => '72–88°F',
                'status' => 'Peak Dry Sunshine',
                'risk' => 'low',
                'risk_label' => 'Prime (Gold)',
                'swimming' => 'Ideal (27–28°C); crystal clear turquoise',
                'elevation_offset' => 'Evening breezes drop Saigon temperatures to a comfortable 22°C.',
                'highlights' => 'Warm festive escape; Phu Quoc and Con Dao resorts at peak demand.',
            ],
            'radar' => [
                'event' => 'Christmas, New Year & Dalat Flower Festival',
                'type' => 'highlight',
                'note' => 'Peak international arrival season. Christmas Eve in Hanoi Old Quarter and Saigon District 1 features massive street gatherings. Reserve flights & bay cruises 2 months ahead.',
            ],
            'checklist' => [
                ['name' => 'Warm thermal base layer & wool socks', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Essential for Sa Pa and northern mountain homestays'],
                ['name' => 'Packable down jacket or thick fleece', 'category' => 'clothing', 'source' => 'bring', 'note' => 'Crucial for northern open-air boat rides'],
                ['name' => 'Light summer shorts & swimwear', 'category' => 'clothing', 'source' => 'buy', 'note' => 'For Phu Quoc sunshine and hotel pools'],
                ['name' => 'Lip balm & hydrating skin moisturizer', 'category' => 'health', 'source' => 'bring', 'note' => 'Protects against dry, breezy northern winter air'],
                ['name' => 'DEET insect spray', 'category' => 'health', 'source' => 'bring', 'note' => 'For southern island beach evenings'],
            ],
            'routes' => [
                ['title' => '14-Day Slow & Balanced Route', 'url' => '/itineraries/14-days-in-vietnam/', 'fit' => 'Classic winter escape with dual mountain & beach modules'],
                ['title' => 'Phu Quoc Travel Guide', 'url' => '/destinations/phu-quoc-travel-guide/', 'fit' => 'Peak winter sun beach destination'],
                ['title' => 'Where to Stay in Hanoi', 'url' => '/destinations/where-to-stay-in-hanoi/', 'fit' => 'Choose central Old Quarter hotel with heating'],
            ],
        ],
    ];
}

/**
 * Renders the interactive Seasonality & Weather Matrix HTML markup.
 *
 * @return string
 */
function vg_render_season_matrix_html(): string
{
    $months = vg_get_season_matrix_dataset();
    ob_start();
    ?>
    <section class="vg-season-matrix" id="vg-season-matrix" aria-label="<?php echo esc_attr__('Vietnam Regional Weather & Packing Matrix', 'vietnamguide-premium'); ?>">
        <!-- Header -->
        <div class="vg-sm-header">
            <div class="vg-sm-heading-group">
                <span class="vg-sm-badge"><?php esc_html_e('Interactive Travel Intelligence', 'vietnamguide-premium'); ?></span>
                <div class="vg-sm-title" role="heading" aria-level="2"><?php esc_html_e('Vietnam Regional Weather & Packing Matrix', 'vietnamguide-premium'); ?></div>
                <p class="vg-sm-subtitle"><?php esc_html_e('Vietnam has three distinct climate zones with opposite monsoons. Select your travel month to see real-world conditions across North, Central, and South Vietnam, route recommendations, and a smart packing checklist.', 'vietnamguide-premium'); ?></p>
            </div>
            <div class="vg-sm-header-controls">
                <!-- Dual Perspective Mode Switcher -->
                <div class="vg-sm-mode-toggle" role="tablist" aria-label="<?php echo esc_attr__('Matrix View Mode', 'vietnamguide-premium'); ?>">
                    <button type="button" class="vg-sm-mode-btn is-active" data-mode="month" role="tab" aria-selected="true" id="vg-tab-month" aria-controls="vg-sm-month-panel">
                        <?php esc_html_e('By Travel Month', 'vietnamguide-premium'); ?>
                    </button>
                    <button type="button" class="vg-sm-mode-btn" data-mode="route" role="tab" aria-selected="false" id="vg-tab-route" aria-controls="vg-sm-route-panel">
                        <?php esc_html_e('Route Heatmap', 'vietnamguide-premium'); ?>
                    </button>
                </div>
                <!-- Temp Unit Switcher -->
                <div class="vg-sm-unit-toggle" role="group" aria-label="<?php echo esc_attr__('Temperature Unit', 'vietnamguide-premium'); ?>">
                    <button type="button" class="vg-sm-unit-btn is-active" data-unit="c" aria-pressed="true">°C</button>
                    <button type="button" class="vg-sm-unit-btn" data-unit="f" aria-pressed="false">°F</button>
                </div>
            </div>
        </div>

        <!-- Mode A: By Travel Month Panel -->
        <div id="vg-sm-month-panel" class="vg-sm-panel" role="tabpanel" aria-labelledby="vg-tab-month">
            <!-- Month Selector Bar -->
            <div class="vg-sm-months-nav" role="tablist" aria-label="<?php echo esc_attr__('Months of the year', 'vietnamguide-premium'); ?>">
                <?php foreach ($months as $mKey => $mData) : ?>
                    <button type="button" class="vg-sm-month-pill<?php echo $mKey === 'nov' ? ' is-active' : ''; ?>" data-month="<?php echo esc_attr($mKey); ?>" role="tab" aria-selected="<?php echo $mKey === 'nov' ? 'true' : 'false'; ?>">
                        <span class="vg-sm-m-short"><?php echo esc_html(substr($mData['name'], 0, 3)); ?></span>
                        <span class="vg-sm-m-season"><?php echo esc_html($mData['season_label']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Strategic Monthly Verdict Banner -->
            <div class="vg-sm-verdict-banner" id="vg-sm-verdict-box">
                <div class="vg-sm-verdict-tag"><?php esc_html_e('Strategic Route Verdict', 'vietnamguide-premium'); ?></div>
                <h3 class="vg-sm-verdict-title" id="vg-sm-verdict-title">November Overview</h3>
                <p class="vg-sm-verdict-text" id="vg-sm-verdict-text"><?php echo esc_html($months['nov']['verdict']); ?></p>
            </div>

            <!-- Regional 3-Column Climate Grid -->
            <div class="vg-sm-regions-grid" id="vg-sm-regions-grid">
                <!-- North Vietnam -->
                <article class="vg-sm-region-card vg-card-north" aria-labelledby="vg-title-north">
                    <div class="vg-sm-card-top">
                        <span class="vg-sm-region-kicker"><?php esc_html_e('Zone 1: Northern Peaks & Karsts', 'vietnamguide-premium'); ?></span>
                        <span class="vg-sm-risk-badge vg-risk-low" id="vg-risk-north">Prime</span>
                    </div>
                    <h3 class="vg-sm-region-name" id="vg-title-north">North Vietnam</h3>
                    <p class="vg-sm-region-cities">Hanoi, Ha Long Bay, Sa Pa, Ninh Binh, Ha Giang</p>
                    
                    <div class="vg-sm-temp-box">
                        <span class="vg-sm-temp-val" id="vg-temp-north">18–25°C</span>
                        <span class="vg-sm-weather-status" id="vg-status-north">Dry, Crisp & Sunny</span>
                    </div>

                    <div class="vg-sm-metrics-list">
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">🏊</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Halong Bay Swimming:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-swim-north">Cool (21°C); swimming bracing</span>
                            </div>
                        </div>
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">⛰️</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Elevation Offset:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-elev-north">Sa Pa drops to 8–12°C at night.</span>
                            </div>
                        </div>
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">✨</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Route Highlights:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-high-north">Superb trekking conditions with minimal mud.</span>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Central Vietnam -->
                <article class="vg-sm-region-card vg-card-central" aria-labelledby="vg-title-central">
                    <div class="vg-sm-card-top">
                        <span class="vg-sm-region-kicker"><?php esc_html_e('Zone 2: Central Heritage Coast', 'vietnamguide-premium'); ?></span>
                        <span class="vg-sm-risk-badge vg-risk-moderate" id="vg-risk-central">Moderate</span>
                    </div>
                    <h3 class="vg-sm-region-name" id="vg-title-central">Central Vietnam</h3>
                    <p class="vg-sm-region-cities">Hue, Da Nang, Hoi An, Phong Nha, Quy Nhon</p>
                    
                    <div class="vg-sm-temp-box">
                        <span class="vg-sm-temp-val" id="vg-temp-central">21–26°C</span>
                        <span class="vg-sm-weather-status" id="vg-status-central">Rains Easing Late Month</span>
                    </div>

                    <div class="vg-sm-metrics-list">
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">🌊</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Sea & Beach State:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-swim-central">Rough seas early month; clears late Nov</span>
                            </div>
                        </div>
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">🏔️</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Microclimate Divide:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-elev-central">Hue remains wetter than Da Nang/Hoi An.</span>
                            </div>
                        </div>
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">🏮</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Route Highlights:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-high-central">Hoi An recovers into cooler pleasant weather.</span>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- South Vietnam -->
                <article class="vg-sm-region-card vg-card-south" aria-labelledby="vg-title-south">
                    <div class="vg-sm-card-top">
                        <span class="vg-sm-region-kicker"><?php esc_html_e('Zone 3: Southern Sun & Islands', 'vietnamguide-premium'); ?></span>
                        <span class="vg-sm-risk-badge vg-risk-low" id="vg-risk-south">Prime (Gold)</span>
                    </div>
                    <h3 class="vg-sm-region-name" id="vg-title-south">South Vietnam</h3>
                    <p class="vg-sm-region-cities">Ho Chi Minh City, Mekong Delta, Phu Quoc, Con Dao</p>
                    
                    <div class="vg-sm-temp-box">
                        <span class="vg-sm-temp-val" id="vg-temp-south">23–31°C</span>
                        <span class="vg-sm-weather-status" id="vg-status-south">Dry Season Commences</span>
                    </div>

                    <div class="vg-sm-metrics-list">
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">🏖️</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Island Sea State:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-swim-south">Warm, flat & transparent (28°C)</span>
                            </div>
                        </div>
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">⛵</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Island Sheltering:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-elev-south">Phu Quoc west coast beaches mirror-calm.</span>
                            </div>
                        </div>
                        <div class="vg-sm-metric-item">
                            <span class="vg-sm-m-icon">🌴</span>
                            <div class="vg-sm-m-desc">
                                <strong><?php esc_html_e('Route Highlights:', 'vietnamguide-premium'); ?></strong>
                                <span id="vg-high-south">Best island resort weather of the year begins.</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Festival & Cultural Radar -->
            <div class="vg-sm-radar-card" id="vg-sm-radar-box">
                <div class="vg-sm-radar-icon">🏮</div>
                <div class="vg-sm-radar-content">
                    <span class="vg-sm-radar-tag"><?php esc_html_e('Cultural & Festival Radar', 'vietnamguide-premium'); ?></span>
                    <h4 class="vg-sm-radar-title" id="vg-radar-event">Ok Om Bok Festival & Buckwheat Blooms</h4>
                    <p class="vg-sm-radar-note" id="vg-radar-note">Khmer Moon-Worship Festival in the Mekong Delta with thrilling traditional ghe ngo boat races. Buckwheat flower peaks in Ha Giang.</p>
                </div>
            </div>

            <!-- Dynamic Packing Checklist Component -->
            <div class="vg-sm-packing-section" id="vg-sm-packing-section">
                <div class="vg-sm-packing-header">
                    <div>
                        <span class="vg-sm-packing-badge"><?php esc_html_e('Interactive Checklist', 'vietnamguide-premium'); ?></span>
                        <h3 class="vg-sm-packing-title"><?php esc_html_e('Smart Packing List & Gear Advice', 'vietnamguide-premium'); ?></h3>
                        <p class="vg-sm-packing-subtitle"><?php esc_html_e('Tailored for your selected month. Check items as you pack (saved to your browser).', 'vietnamguide-premium'); ?></p>
                    </div>
                    <div class="vg-sm-packing-metrics">
                        <div class="vg-sm-progress-wrap">
                            <span class="vg-sm-progress-text" id="vg-sm-progress-count">0 of 10 items packed (0%)</span>
                            <div class="vg-sm-progress-bar">
                                <div class="vg-sm-progress-fill" id="vg-sm-progress-fill" style="width: 0%;"></div>
                            </div>
                        </div>
                        <div class="vg-sm-baggage-note">
                            <span class="vg-sm-bag-icon">⚖️</span>
                            <span><strong><?php esc_html_e('Estimated Carry-on Weight:', 'vietnamguide-premium'); ?></strong> ~5.2 kg / 11.5 lbs (Safe for 7kg domestic airline limits)</span>
                        </div>
                    </div>
                </div>

                <!-- Checklist Items Grid -->
                <div class="vg-sm-checklist-grid" id="vg-sm-checklist-container">
                    <!-- Populated via JavaScript dynamically -->
                </div>

                <!-- Actions Bar -->
                <div class="vg-sm-actions-bar">
                    <button type="button" class="vg-sm-btn vg-sm-btn-copy" id="vg-sm-copy-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <span><?php esc_html_e('Copy Month Briefing & Checklist', 'vietnamguide-premium'); ?></span>
                    </button>
                    <button type="button" class="vg-sm-btn vg-sm-btn-print" id="vg-sm-print-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span><?php esc_html_e('Print / Save PDF', 'vietnamguide-premium'); ?></span>
                    </button>
                    <button type="button" class="vg-sm-btn-reset" id="vg-sm-reset-btn">
                        <?php esc_html_e('Reset Checklist', 'vietnamguide-premium'); ?>
                    </button>
                </div>
            </div>

            <!-- Contextual Route Fit Guides -->
            <div class="vg-sm-routes-section">
                <h4 class="vg-sm-routes-heading"><?php esc_html_e('Recommended Next Route Guides for this Month:', 'vietnamguide-premium'); ?></h4>
                <div class="vg-sm-routes-grid" id="vg-sm-routes-container">
                    <!-- Populated via JavaScript dynamically -->
                </div>
            </div>
        </div>

        <!-- Mode B: Route Heatmap Panel -->
        <div id="vg-sm-route-panel" class="vg-sm-panel" role="tabpanel" aria-labelledby="vg-tab-route" hidden>
            <div class="vg-sm-heatmap-intro">
                <h3><?php esc_html_e('12-Month Route & Season Heatmap', 'vietnamguide-premium'); ?></h3>
                <p><?php esc_html_e('Already know where you want to travel? See the optimal months of the year for each core Vietnam journey archetype.', 'vietnamguide-premium'); ?></p>
            </div>
            
            <div class="vg-sm-heatmap-table-wrap">
                <table class="vg-sm-heatmap-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Journey Archetype', 'vietnamguide-premium'); ?></th>
                            <th scope="col">Jan</th><th scope="col">Feb</th><th scope="col">Mar</th>
                            <th scope="col">Apr</th><th scope="col">May</th><th scope="col">Jun</th>
                            <th scope="col">Jul</th><th scope="col">Aug</th><th scope="col">Sep</th>
                            <th scope="col">Oct</th><th scope="col">Nov</th><th scope="col">Dec</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">
                                <strong>10-Day Classic Vietnam</strong>
                                <span>Hanoi, Halong, Hoi An, HCMC</span>
                            </th>
                            <td><span class="vg-heat-cell vg-heat-good" title="Good">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Good">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Warm/Rain">Mod</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Storm risk">Mod</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Good">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Good">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-high" title="Central flood">Flood</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Good">Good</span></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <strong>Northern Peaks & Karsts</strong>
                                <span>Sa Pa, Ha Giang, Halong, Ninh Binh</span>
                            </th>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Chilly/Fog">Cold</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Cool/Dry">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Mirror season">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Summer rain">Rain</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Summer rain">Rain</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Early harvest">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Golden harvest">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Buckwheat flower">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Dry & sunny">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Chilly winter">Cold</span></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <strong>Central Coast & Heritage</strong>
                                <span>Hue, Da Nang, Hoi An, Quy Nhon</span>
                            </th>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Misty rain">Mod</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Sunny & dry">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime beach">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Prime beach">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Peak sun">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Peak beach">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Calm sea">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Good">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Rains start">Rain</span></td>
                            <td><span class="vg-heat-cell vg-heat-high" title="High flood risk">Flood</span></td>
                            <td><span class="vg-heat-cell vg-heat-high" title="High flood risk">Flood</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Cool overcast">Mod</span></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <strong>Southern Sun & Islands</strong>
                                <span>HCMC, Mekong, Phu Quoc, Con Dao</span>
                            </th>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Peak dry">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Peak dry">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Peak dry">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Warm/dry">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Early rain">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Afternoon rain">Rain</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Afternoon rain">Rain</span></td>
                            <td><span class="vg-heat-cell vg-heat-mod" title="Afternoon rain">Rain</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Floating season">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-good" title="Dry returning">Good</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Gold season">Prime</span></td>
                            <td><span class="vg-heat-cell vg-heat-prime" title="Peak sun">Prime</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="vg-sm-heatmap-legend">
                <span class="vg-legend-item"><span class="vg-heat-cell vg-heat-prime">Prime</span> Gold Standard (Calm weather, best visibility)</span>
                <span class="vg-legend-item"><span class="vg-heat-cell vg-heat-good">Good</span> Pleasant & Reliable (Occasional light rain)</span>
                <span class="vg-legend-item"><span class="vg-heat-cell vg-heat-mod">Mod</span> Moderate Friction (Chilly high peaks or brief afternoon rain)</span>
                <span class="vg-legend-item"><span class="vg-heat-cell vg-heat-high">Flood</span> High Risk / Pivot (Heavy monsoon flooding / rough seas)</span>
            </div>
        <div class="vg-sm-bridge vg-tool-synergy-bar">
            <div class="vg-sm-bridge-head">
                <span class="vg-sm-bridge-icon">🧳</span>
                <div class="vg-sm-bridge-title"><?php esc_html_e('Continue Planning Your Vietnam Journey', 'vietnamguide-premium'); ?></div>
            </div>
            <div class="vg-sm-bridge-grid">
                <a href="<?php echo esc_url(home_url('/plan/vietnam-evisa/')); ?>" class="vg-sm-bridge-card vg-synergy-bridge">
                    <span class="vg-sm-bridge-tag"><?php esc_html_e('Visa & Entry', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Visa Exemption & E-Visa Checker', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Verify 45-day exemption vs $25 e-visa rules &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/costs/vietnam-travel-cost/')); ?>" class="vg-sm-bridge-card vg-synergy-bridge">
                    <span class="vg-sm-bridge-tag"><?php esc_html_e('Budgeting', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Trip Cost Calculator', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Estimate 3–30 day spending by comfort tier &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/plan/vietnam-airport-arrival-checklist/')); ?>" class="vg-sm-bridge-card vg-synergy-bridge">
                    <span class="vg-sm-bridge-tag"><?php esc_html_e('Airport Transit', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Airport Transit Navigator', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Grab bays & scam shields for HAN, SGN & DAD &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/itineraries/')); ?>" class="vg-sm-bridge-card vg-synergy-bridge">
                    <span class="vg-sm-bridge-tag"><?php esc_html_e('Routes', 'vietnamguide-premium'); ?></span>
                    <strong><?php esc_html_e('Smart Route & Itinerary Finder', 'vietnamguide-premium'); ?></strong>
                    <span><?php esc_html_e('Filter 7, 10, 14, 21-day routes &rarr;', 'vietnamguide-premium'); ?></span>
                </a>
            </div>
        </div>

        <div id="vg-season-aria-status" class="screen-reader-text" aria-live="polite"></div>
    </section>

    <!-- Embedded Raw Data in Script -->
    <script id="vg-season-matrix-raw-data" type="application/json">
    <?php echo wp_json_encode($months); ?>
    </script>

    <script>
    (function () {
        'use strict';

        var rawDataEl = document.getElementById('vg-season-matrix-raw-data');
        if (!rawDataEl) return;

        var DATA;
        try {
            DATA = JSON.parse(rawDataEl.textContent);
        } catch (e) {
            return;
        }

        var state = {
            currentMonth: 'nov',
            unit: 'c',
            mode: 'month',
            checkedItems: {}
        };

        // Priority 1: Check URL hash (#month-mar, #month-nov, etc.)
        var hashMatch = (window.location.hash || '').toLowerCase().replace('#month-', '').replace('#', '');
        var monthKeys = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        if (DATA[hashMatch]) {
            state.currentMonth = hashMatch;
        } else {
            // Priority 2: Check query param (?month=mar or ?month=3)
            try {
                var urlParams = new URLSearchParams(window.location.search);
                var qMonth = urlParams.get('month');
                if (qMonth) {
                    var qLower = qMonth.toLowerCase();
                    if (DATA[qLower]) {
                        state.currentMonth = qLower;
                    } else {
                        var mNum = parseInt(qMonth, 10);
                        if (mNum >= 1 && mNum <= 12 && DATA[monthKeys[mNum - 1]]) {
                            state.currentMonth = monthKeys[mNum - 1];
                        }
                    }
                } else {
                    // Priority 3: Check sessionStorage, then localStorage
                    var sessionMonth = sessionStorage.getItem('vg_user_month');
                    if (sessionMonth && DATA[sessionMonth]) {
                        state.currentMonth = sessionMonth;
                    } else {
                        var savedMonth = localStorage.getItem('vg_sm_month');
                        if (savedMonth && DATA[savedMonth]) state.currentMonth = savedMonth;
                    }
                }
            } catch(e) {}
        }

        try {
            var sessionUnit = sessionStorage.getItem('vg_user_temp_unit');
            if (sessionUnit && (sessionUnit === 'c' || sessionUnit === 'f')) {
                state.unit = sessionUnit;
            } else {
                var savedUnit = localStorage.getItem('vg_sm_unit');
                if (savedUnit && (savedUnit === 'c' || savedUnit === 'f')) state.unit = savedUnit;
            }

            var savedChecks = localStorage.getItem('vg_sm_checks');
            if (savedChecks) state.checkedItems = JSON.parse(savedChecks);
        } catch (e) {}

        function saveState() {
            try {
                sessionStorage.setItem('vg_user_month', state.currentMonth);
                sessionStorage.setItem('vg_user_temp_unit', state.unit);
                localStorage.setItem('vg_sm_month', state.currentMonth);
                localStorage.setItem('vg_sm_unit', state.unit);
                localStorage.setItem('vg_sm_checks', JSON.stringify(state.checkedItems));
            } catch (e) {}

            if (window.history && window.history.replaceState) {
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('month', state.currentMonth);
                    window.history.replaceState(null, '', url.toString());
                } catch (e) {}
            }
        }

        function updateView() {
            var m = DATA[state.currentMonth];
            if (!m) return;

            // Aria live announcement
            var ariaStatus = document.getElementById('vg-season-aria-status');
            if (ariaStatus) {
                ariaStatus.textContent = 'Active climate briefing updated to ' + m.name + ' (' + m.season_label + '). North: ' + m.north.status + ', Central: ' + m.central.status + ', South: ' + m.south.status;
            }

            // Verdict
            var verdictTitle = document.getElementById('vg-sm-verdict-title');
            var verdictText = document.getElementById('vg-sm-verdict-text');
            if (verdictTitle) verdictTitle.textContent = m.name + ' Strategic Overview';
            if (verdictText) verdictText.textContent = m.verdict;

            // Update North
            updateRegion('north', m.north);
            // Update Central
            updateRegion('central', m.central);
            // Update South
            updateRegion('south', m.south);

            // Update Radar
            var radarEvent = document.getElementById('vg-radar-event');
            var radarNote = document.getElementById('vg-radar-note');
            if (radarEvent) radarEvent.textContent = m.radar.event;
            if (radarNote) radarNote.textContent = m.radar.note;

            // Update Checklist
            renderChecklist(m.checklist);

            // Update Recommended Routes
            renderRoutes(m.routes);

            // Update ARIA status
            var ariaEl = document.getElementById('vg-season-aria-status');
            if (ariaEl) {
                ariaEl.textContent = 'Viewing Vietnam weather intelligence for ' + m.name + '. ' + m.verdict;
            }
        }

        function updateRegion(regKey, regData) {
            var tempEl = document.getElementById('vg-temp-' + regKey);
            var statusEl = document.getElementById('vg-status-' + regKey);
            var riskEl = document.getElementById('vg-risk-' + regKey);
            var swimEl = document.getElementById('vg-swim-' + regKey);
            var elevEl = document.getElementById('vg-elev-' + regKey);
            var highEl = document.getElementById('vg-high-' + regKey);

            if (tempEl) {
                tempEl.textContent = state.unit === 'c' ? regData.temp_c : regData.temp_f;
            }
            if (statusEl) statusEl.textContent = regData.status;

            if (riskEl) {
                riskEl.textContent = regData.risk_label;
                riskEl.className = 'vg-sm-risk-badge vg-risk-' + regData.risk;
            }
            if (swimEl) swimEl.textContent = regData.swimming;
            if (elevEl) elevEl.textContent = regData.elevation_offset;
            if (highEl) highEl.textContent = regData.highlights;
        }

        function renderChecklist(items) {
            var container = document.getElementById('vg-sm-checklist-container');
            if (!container) return;

            container.innerHTML = '';
            var total = items.length;
            var packedCount = 0;

            items.forEach(function (item, idx) {
                var itemKey = state.currentMonth + '_' + idx;
                var isChecked = !!state.checkedItems[itemKey];
                if (isChecked) packedCount++;

                var row = document.createElement('label');
                row.className = 'vg-sm-check-item' + (isChecked ? ' is-checked' : '');

                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'vg-sm-checkbox';
                checkbox.checked = isChecked;

                checkbox.addEventListener('change', function () {
                    if (checkbox.checked) {
                        state.checkedItems[itemKey] = true;
                        row.classList.add('is-checked');
                    } else {
                        delete state.checkedItems[itemKey];
                        row.classList.remove('is-checked');
                    }
                    saveState();
                    updateProgress(items.length);
                });

                var body = document.createElement('div');
                body.className = 'vg-sm-item-body';

                var top = document.createElement('div');
                top.className = 'vg-sm-item-top';

                var name = document.createElement('span');
                name.className = 'vg-sm-item-name';
                name.textContent = item.name;

                var tag = document.createElement('span');
                tag.className = 'vg-sm-item-tag vg-tag-' + item.source;
                tag.textContent = item.source === 'bring' ? 'Bring from Home' : 'Buy in Vietnam';

                top.appendChild(name);
                top.appendChild(tag);

                var note = document.createElement('span');
                note.className = 'vg-sm-item-note';
                note.textContent = item.note;

                body.appendChild(top);
                body.appendChild(note);

                row.appendChild(checkbox);
                row.appendChild(body);
                container.appendChild(row);
            });

            updateProgress(total);
        }

        function updateProgress(total) {
            var packed = 0;
            for (var i = 0; i < total; i++) {
                if (state.checkedItems[state.currentMonth + '_' + i]) packed++;
            }
            var pct = total > 0 ? Math.round((packed / total) * 100) : 0;

            var countEl = document.getElementById('vg-sm-progress-count');
            var fillEl = document.getElementById('vg-sm-progress-fill');

            if (countEl) countEl.textContent = packed + ' of ' + total + ' items packed (' + pct + '%)';
            if (fillEl) fillEl.style.width = pct + '%';
        }

        function renderRoutes(routes) {
            var container = document.getElementById('vg-sm-routes-container');
            if (!container) return;

            container.innerHTML = '';
            routes.forEach(function (r) {
                var card = document.createElement('a');
                card.className = 'vg-sm-route-chip';
                card.href = r.url;

                var title = document.createElement('strong');
                title.textContent = r.title;

                var fit = document.createElement('span');
                fit.textContent = r.fit;

                var arrow = document.createElement('span');
                arrow.className = 'vg-sm-route-arrow';
                arrow.innerHTML = '&rarr;';

                card.appendChild(title);
                card.appendChild(fit);
                card.appendChild(arrow);
                container.appendChild(card);
            });
        }

        function init() {
            var root = document.getElementById('vg-season-matrix');
            if (!root) return;

            // Month Pills
            var pills = root.querySelectorAll('.vg-sm-month-pill');
            pills.forEach(function (pill, idx) {
                var mKey = pill.getAttribute('data-month');
                var isActive = (mKey === state.currentMonth);
                pill.classList.toggle('is-active', isActive);
                pill.setAttribute('aria-selected', isActive ? 'true' : 'false');
                pill.setAttribute('tabindex', isActive ? '0' : '-1');

                pill.addEventListener('click', function () {
                    state.currentMonth = mKey;
                    pills.forEach(function (p) {
                        p.classList.remove('is-active');
                        p.setAttribute('aria-selected', 'false');
                        p.setAttribute('tabindex', '-1');
                    });
                    pill.classList.add('is-active');
                    pill.setAttribute('aria-selected', 'true');
                    pill.setAttribute('tabindex', '0');
                    saveState();
                    updateView();
                });

                pill.addEventListener('keydown', function (e) {
                    var targetIdx = -1;
                    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                        targetIdx = (idx + 1) % pills.length;
                    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                        targetIdx = (idx - 1 + pills.length) % pills.length;
                    } else if (e.key === 'Home') {
                        targetIdx = 0;
                    } else if (e.key === 'End') {
                        targetIdx = pills.length - 1;
                    }

                    if (targetIdx !== -1) {
                        e.preventDefault();
                        pills[targetIdx].focus();
                        pills[targetIdx].click();
                    }
                });
            });

            // Temperature Unit Toggle
            var unitBtns = root.querySelectorAll('.vg-sm-unit-btn');
            unitBtns.forEach(function (btn) {
                var u = btn.getAttribute('data-unit');
                btn.classList.toggle('is-active', u === state.unit);
                btn.setAttribute('aria-pressed', u === state.unit ? 'true' : 'false');

                btn.addEventListener('click', function () {
                    state.unit = u;
                    unitBtns.forEach(function (b) { b.classList.remove('is-active'); b.setAttribute('aria-pressed', 'false'); });
                    btn.classList.add('is-active');
                    btn.setAttribute('aria-pressed', 'true');
                    saveState();
                    updateView();
                });
            });

            // Mode Switcher (Month vs Route Heatmap)
            var modeBtns = root.querySelectorAll('.vg-sm-mode-btn');
            var monthPanel = document.getElementById('vg-sm-month-panel');
            var routePanel = document.getElementById('vg-sm-route-panel');

            modeBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var m = btn.getAttribute('data-mode');
                    modeBtns.forEach(function (b) { b.classList.remove('is-active'); b.setAttribute('aria-selected', 'false'); });
                    btn.classList.add('is-active');
                    btn.setAttribute('aria-selected', 'true');

                    if (m === 'route') {
                        if (monthPanel) monthPanel.hidden = true;
                        if (routePanel) routePanel.hidden = false;
                    } else {
                        if (monthPanel) monthPanel.hidden = false;
                        if (routePanel) routePanel.hidden = true;
                    }
                });
            });

            // Copy Action
            var copyBtn = document.getElementById('vg-sm-copy-btn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    var m = DATA[state.currentMonth];
                    if (!m) return;

                    var text = [
                        'Vietnam Travel Climate & Packing Briefing (VietnamGuide.net)',
                        '==========================================================',
                        'Month: ' + m.name + ' (' + m.season_label + ')',
                        'Verdict: ' + m.verdict,
                        '',
                        'REGIONAL BREAKDOWN:',
                        '- North Vietnam: ' + m.north.temp_c + ' (' + m.north.status + ') - Swimming: ' + m.north.swimming,
                        '- Central Vietnam: ' + m.central.temp_c + ' (' + m.central.status + ') - Sea: ' + m.central.swimming,
                        '- South Vietnam: ' + m.south.temp_c + ' (' + m.south.status + ') - Islands: ' + m.south.swimming,
                        '',
                        'FESTIVAL RADAR:',
                        '- ' + m.radar.event + ': ' + m.radar.note,
                        '',
                        'PACKING CHECKLIST FOR ' + m.name.toUpperCase() + ':',
                    ];

                    m.checklist.forEach(function (item) {
                        text.push('[ ] ' + item.name + ' (' + (item.source === 'bring' ? 'Bring from home' : 'Buy in Vietnam') + ') - ' + item.note);
                    });

                    text.push('');
                    text.push('Source: https://vietnamguide.net/plan/best-time-to-visit-vietnam/');

                    var summaryStr = text.join('\n');
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(summaryStr).then(function () {
                            var originalHtml = copyBtn.innerHTML;
                            copyBtn.classList.add('is-copied');
                            copyBtn.innerHTML = '<span>Copied to Clipboard!</span>';
                            setTimeout(function () {
                                copyBtn.innerHTML = originalHtml;
                                copyBtn.classList.remove('is-copied');
                            }, 2200);
                        });
                    }
                });
            }

            // Print / PDF Action
            var printBtn = document.getElementById('vg-sm-print-btn');
            if (printBtn) {
                printBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            // Reset Checklist Action
            var resetBtn = document.getElementById('vg-sm-reset-btn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    var m = DATA[state.currentMonth];
                    if (!m) return;
                    for (var i = 0; i < m.checklist.length; i++) {
                        delete state.checkedItems[state.currentMonth + '_' + i];
                    }
                    saveState();
                    renderChecklist(m.checklist);
                });
            }

            // Initial render
            updateView();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
    <?php
    return (string) ob_get_clean();
}

/**
 * Shortcode handler for [vg_season_matrix].
 *
 * @return string
 */
function vg_season_matrix_shortcode(): string
{
    return vg_render_season_matrix_html();
}
add_shortcode('vg_season_matrix', 'vg_season_matrix_shortcode');

/**
 * Injects the Seasonality Matrix on /plan/best-time-to-visit-vietnam/ after hero section.
 *
 * @param string $content Post content.
 * @return string
 */
function vg_inject_season_matrix_on_page(string $content): string
{
    if (is_admin()) {
        return $content;
    }

    // Never inject into hero block
    if (strpos($content, 'vg-guide-hero') !== false) {
        return $content;
    }

    static $injectedPosts = [];
    $postId = get_the_ID();
    if ($postId && isset($injectedPosts[$postId])) {
        return $content;
    }

    $isTargetPage = is_page('best-time-to-visit-vietnam')
        || (is_singular('page') && get_post_field('post_name') === 'best-time-to-visit-vietnam')
        || is_page('what-to-pack-for-vietnam-region-season')
        || (is_singular('page') && get_post_field('post_name') === 'what-to-pack-for-vietnam-region-season')
        || is_page('what-to-pack-vietnam')
        || (is_singular('page') && get_post_field('post_name') === 'what-to-pack-vietnam')
        || is_page('best-time-for-northern-vietnam')
        || (is_singular('page') && get_post_field('post_name') === 'best-time-for-northern-vietnam')
        || is_page('vietnam-rainy-season-flexible-route')
        || (is_singular('page') && get_post_field('post_name') === 'vietnam-rainy-season-flexible-route');

    if (! $isTargetPage) {
        return $content;
    }

    if (has_shortcode($content, 'vg_season_matrix') || strpos($content, 'vg-season-matrix') !== false) {
        return $content;
    }

    if ($postId) {
        $injectedPosts[$postId] = true;
    }

    $matrixHtml = vg_render_season_matrix_html();
    $heroClose = '<!-- /wp:group -->';
    $pos = strpos($content, $heroClose);

    if ($pos !== false) {
        $insertAt = $pos + strlen($heroClose);
        return substr($content, 0, $insertAt) . "\n\n" . $matrixHtml . "\n\n" . substr($content, $insertAt);
    }

    return $matrixHtml . "\n\n" . $content;
}
add_filter('the_content', 'vg_inject_season_matrix_on_page', 20);
