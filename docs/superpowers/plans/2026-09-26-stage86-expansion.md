# Stage 86: Geographic Authority & Gateway Transit Expansion (280 → 287 Pages)

## Goal
Expand VietnamGuide's comprehensive coverage from 280 to 287 published pages by authoring, testing, deploying, and cross-linking 7 high-intent travel pillars across island volcanic archipelagos, Central Highland pine plateaus, northern national park lakes, interprovincial cave transit corridors, mountain valley passes, and Gulf of Thailand island ferries.

## Architecture & Constraints
- WordPress CMS on LiteSpeed Enterprise VPS (`66.42.48.146:2209`).
- "Tối ưu nội dung, không thêm chức năng mới" — Zero new plugins, widgets, shortcodes, or database schema modifications.
- Anti-AI Slop v3.0: 100/100 HLS score, 0 Tier 1 clichés on all authored content.
- Evidence Density Index (EDI): minimum 15 explicit VND + USD price pairs and named travel entities per pillar.
- SERP metadata: Titles 40–60 characters (with 2026 freshness year), Meta Descriptions 130–155 characters.
- Link Mesh Guarantee: Every new pillar must receive at least 4 verified live inbound links from existing host posts.
- Sitewide E-E-A-T timestamps synchronized to September 26, 2026.
- All 5 automated CI/CD Quality Gates must pass before Git commit and push.

---

## 7 Proposed Pillars (Stage 86)

1. **`where-to-stay-in-ly-son`** (Parent 7, Destinations)
   - Focus: Dao Lon (Big Island) port hotels vs Dao Be (Little Island) volcanic beach homestays and Mu Cu sunrise eco-lodges.
   - Title: `Where to Stay in Ly Son (2026): Big vs Small Island` (55 chars)
   - Meta Desc: `Where to stay in Ly Son: Big Island port hotels vs Little Island volcanic beach homestays and Mu Cu sunrise eco-lodges with 2026 room rates and island advice.` (154 chars)
   - Focus Keyword: `Where to Stay in Ly Son`

2. **`mang-den-travel-guide`** (Parent 7, Destinations)
   - Focus: Pa Sy waterfall, pine forest trails, Kon Pring cultural village, 7 lakes, climate, cafes, and 2-3 day itineraries in the Central Highlands.
   - Title: `Mang Den Travel Guide (2026): Central Highlands Pine Oasis` (58 chars)
   - Meta Desc: `Complete Mang Den travel guide: Pa Sy waterfall, pine forest trails, Kon Pring village, 2026 weather, cafes, road access from Kon Tum, and 3-day itineraries.` (155 chars)
   - Focus Keyword: `Mang Den Travel Guide`

3. **`ba-be-lake-travel-guide`** (Parent 7, Destinations)
   - Focus: Ba Be National Park boat tours, Puong Cave river passage, Dau Dang waterfall, Pac Ngoi Tay stilt homestays, kayak rentals, and loop routes.
   - Title: `Ba Be Lake Travel Guide (2026): Boat Tours & Homestays` (55 chars)
   - Meta Desc: `Ba Be Lake travel guide: scenic boat tours, Puong Cave river passage, Dau Dang waterfall, Pac Ngoi homestays, kayak rentals, park fees, and 2026 itineraries.` (155 chars)
   - Focus Keyword: `Ba Be Lake Travel Guide`

4. **`da-nang-to-ly-son-transport`** (Parent 6, Plan)
   - Focus: Trains to Quang Ngai, connecting taxis to Sa Ky Port, and fast speedboats to Dao Lon. Ticket fares, schedules, and ferry weather advice.
   - Title: `Da Nang to Ly Son Transport (2026): Train, Bus & Ferry` (54 chars)
   - Meta Desc: `Da Nang to Ly Son transport: trains to Quang Ngai, taxis to Sa Ky Port, and speedboats to Dao Lon. 2026 ticket fares, schedules, and ferry weather advice.` (151 chars)
   - Focus Keyword: `Da Nang to Ly Son Transport`

5. **`hue-to-phong-nha-transport`** (Parent 6, Plan)
   - Focus: Direct tourist buses, trains to Dong Hoi with cave shuttles, and DMZ private car tours. 2026 fares, schedules, and route advice.
   - Title: `Hue to Phong Nha Transport (2026): Bus, Train & DMZ` (51 chars)
   - Meta Desc: `Hue to Phong Nha transport: direct tourist buses, trains to Dong Hoi with cave shuttles, and DMZ private car tours. 2026 fares, schedules, and route advice.` (153 chars)
   - Focus Keyword: `Hue to Phong Nha Transport`

6. **`mai-chau-to-pu-luong-transport`** (Parent 6, Plan)
   - Focus: Shared shuttle vans vs private cars via Highway 15C. 2026 transfer times, fares, and mountain road travel advice.
   - Title: `Mai Chau to Pu Luong Transport (2026): Van vs Bike` (50 chars)
   - Meta Desc: `Mai Chau to Pu Luong transport: shared shuttle vans vs private cars via Highway 15C. 2026 transfer times, fares, and mountain road travel advice.` (143 chars)
   - Focus Keyword: `Mai Chau to Pu Luong Transport`

7. **`ha-tien-to-phu-quoc-ferry`** (Parent 6, Plan)
   - Focus: Superdong vs Phu Quoc Express fast catamarans. 2026 ticket fares, daily timetables, pier logistics, and vehicle boarding advice.
   - Title: `Ha Tien to Phu Quoc Ferry (2026): Speedboat Guide` (50 chars)
   - Meta Desc: `Ha Tien to Phu Quoc ferry: Superdong vs Phu Quoc Express fast catamarans. 2026 ticket fares, daily timetables, pier logistics, and vehicle boarding advice.` (152 chars)
   - Focus Keyword: `Ha Tien to Phu Quoc Ferry`

---

## 10-Task Execution Pipeline

### Task 1: Content Authoring (7 Content Modules)
- Author all 7 content modules in `ops/` with 100/100 HLS, zero clichés, real VND/USD rates and structured HTML components.

### Task 2: Automated Quality Validation
- Create `ops/test_stage86_content.py` and verify all 7 modules pass HLS, cliché, SERP, and Evidence Density Index checks.

### Task 3: Production VPS Deployment
- Deploy the 7 pillars to production VPS via WP-CLI with parent mapping (`destinations` 7, `plan` 6) and Rank Math SEO metadata. Verify HTTP 200.

### Task 4: Mesh Target Post Extraction
- Fetch candidate host posts to `ops/stage86_mesh_targets.json`.

### Task 5: Candidate String Verification & Mesh Assembly
- Verify exact count=1 replacements and assemble `ops/stage86_mesh_updates.json`.

### Task 6: Apply Mesh & Purge Cache
- Update post_content across host posts and purge LiteSpeed cache.

### Task 7: Inbound Link & Tag Verification
- Verify >= 4 inbound links per pillar and 0 malformed HTML tags.

### Task 8: Discovery, Inventory & E-E-A-T Date Sync
- Update `meta_inventory.json` (280 → 287), sync visual dates to September 26, 2026, verify sitemap, ping IndexNow & WebSub.

### Task 9: CI/CD Quality Gates & Regressions
- Pass all 5 master quality gates and public fixtures.

### Task 10: Documentation & Git Workspace Synchronization
- Record in `task.md` and `walkthrough.md`, commit, push to GitHub `origin/master`, and sync `M:\Projects\vietnamguide`.
