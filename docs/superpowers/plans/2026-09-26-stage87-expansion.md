# Stage 87 Implementation Plan: 7 High-Intent Pillars Expansion (287 → 294 Pages)

> **For agentic workers:** REQUIRED SUB-SKILL: Follow the proven 10-step content & mesh pipeline. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Author, validate, deploy, and link 7 high-intent travel pillars across northern mountain passes, Central Highland transport corridors, Ly Son port hubs, and Gulf of Thailand fast ferry routes, elevating total published pages from 287 to 294.

**Architecture:** Content authoring only ("Tối ưu nội dung, không thêm chức năng mới"). Each pillar enforces 100/100 HLS human-likeness score, zero Tier 1 clichés, explicit dual VND + USD pricing evidence (>= 15 anchors), responsive HTML comparison tables, verified concierge verdicts, valid Rank Math SEO metadata, parent hierarchy (`destinations` 7 / `plan` 6), and a 4+ inlink mesh topology.

**Tech Stack:** WordPress REST API / WP-CLI on production VPS (`66.42.48.146:2209`), Python 3 automation scripts, Rank Math SEO, LiteSpeed Cache, IndexNow API, WebSub hub syndication.

## Global Constraints
- Absolute adherence to: "Tối ưu nội dung, không thêm chức năng mới"
- Zero modifications to plugins, PHP themes, shortcodes, or database schemas
- 100/100 HLS score via `anti_ai_slop_linter.py`
- 0 Tier 1 AI cliches (testament, tapestry, nestled, vibrant, delve, etc.)
- Exact focus keyword match in first 100 words, H1, meta title, and meta description
- Evidence count >= 15 with explicit VND + USD pairs on every authored page
- Strict meta description length: 140–155 characters
- Strict meta title length: 40–60 characters
- Guaranteed >= 4 verified inbound internal links for every new pillar
- All 5 automated CI/CD quality gates must pass before pushing to GitHub

---

## 7 Target Pillars for Stage 87
1. `where-to-stay-in-yen-minh` (Parent ID: 7 `destinations`):
   - Focus KW: `where to stay in Yen Minh`
   - Meta Title: `Where to Stay in Yen Minh (2026): Town vs Pine Valley` (55 chars)
   - Meta Desc: `Where to stay in Yen Minh: town center guesthouses vs pine forest homestays and eco-lodges with 2026 room rates, loop amenities, and Night 1 advice.` (149 chars)

2. `where-to-stay-in-quang-ngai` (Parent ID: 7 `destinations`):
   - Focus KW: `where to stay in Quang Ngai`
   - Meta Title: `Where to Stay in Quang Ngai (2026): City vs Sa Ky Port` (54 chars)
   - Meta Desc: `Where to stay in Quang Ngai: city center hotels vs Sa Ky port transit motels for early Ly Son ferries. 2026 room prices, amenities, and booking advice.` (152 chars)

3. `ha-giang-to-dong-van-transport` (Parent ID: 6 `plan`):
   - Focus KW: `Ha Giang to Dong Van transport`
   - Meta Title: `Ha Giang to Dong Van Transport (2026): Bus vs Motorbike` (55 chars)
   - Meta Desc: `Ha Giang to Dong Van transport: local minibuses vs motorbike self-drive and Easy Riders via QL4C. 2026 fares, travel times, and mountain pass advice.` (148 chars)

4. `rach-gia-to-phu-quoc-ferry` (Parent ID: 6 `plan`):
   - Focus KW: `Rach Gia to Phu Quoc ferry`
   - Meta Title: `Rach Gia to Phu Quoc Ferry (2026): Speedboat Guide` (50 chars)
   - Meta Desc: `Rach Gia to Phu Quoc ferry: Superdong vs Phu Quoc Express fast catamarans. 2026 ticket fares, schedules, terminal logistics, and sea travel advice.` (148 chars)

5. `can-tho-to-phu-quoc-transport` (Parent ID: 6 `plan`):
   - Focus KW: `Can Tho to Phu Quoc transport`
   - Meta Title: `Can Tho to Phu Quoc Transport (2026): Ferry vs Flight` (53 chars)
   - Meta Desc: `Can Tho to Phu Quoc transport: connecting buses via Rach Gia or Ha Tien ferries vs direct flights. 2026 fares, schedules, and Mekong travel advice.` (147 chars)

6. `quy-nhon-to-hoi-an-transport` (Parent ID: 6 `plan`):
   - Focus KW: `Quy Nhon to Hoi An transport`
   - Meta Title: `Quy Nhon to Hoi An Transport (2026): Train, Bus & Car` (52 chars)
   - Meta Desc: `Quy Nhon to Hoi An transport: trains from Dieu Tri to Tra Kieu, limousine sleeper buses, and coastal private cars. 2026 fares, times, and route advice.` (151 chars)

7. `da-lat-to-pleiku-transport` (Parent ID: 6 `plan`):
   - Focus KW: `Da Lat to Pleiku transport`
   - Meta Title: `Da Lat to Pleiku Transport (2026): Sleeper Bus Guide` (52 chars)
   - Meta Desc: `Da Lat to Pleiku transport: direct sleeper buses and limousine vans via QL27 and QL14. 2026 ticket fares, schedules, rest stops, and highland route advice.` (154 chars)

---

## Execution Tasks

- [ ] **Task 1: Author Content Definition Files**
  - Create `ops/content_where_to_stay_in_yen_minh.py`
  - Create `ops/content_where_to_stay_in_quang_ngai.py`
  - Create `ops/content_ha_giang_to_dong_van_transport.py`
  - Create `ops/content_rach_gia_to_phu_quoc_ferry.py`
  - Create `ops/content_can_tho_to_phu_quoc_transport.py`
  - Create `ops/content_quy_nhon_to_hoi_an_transport.py`
  - Create `ops/content_da_lat_to_pleiku_transport.py`

- [ ] **Task 2: Content QA & Slop Linter Suite**
  - Create and run `ops/test_stage87_content.py`
  - Verify 100/100 HLS score, 0 clichés, evidence count >= 15, and SERP length limits.

- [ ] **Task 3: Production VPS Deployment**
  - Create and run `ops/deploy_stage87_pillars.py`
  - Verify HTTP 200, concierge verdict block, comparison tables, and titles for all 7 live URLs.

- [ ] **Task 4: Candidate Link Targets Fetch**
  - Create and run `ops/fetch_stage87_mesh_targets.py`
  - Pull live content for 28 candidate host posts into `ops/stage87_mesh_targets.json`.

- [ ] **Task 5: Mesh Replacement Assembly & Testing**
  - Create `ops/test_mesh87_candidates.py` and verify all 28 replacements match exactly once (`count == 1`).
  - Create and run `ops/assemble_stage87_mesh.py` to produce `ops/stage87_mesh_updates.json`.

- [ ] **Task 6: Production Mesh Deployment**
  - Create and run `ops/apply_stage87_mesh.py`
  - Purge LiteSpeed cache.

- [ ] **Task 7: Inbound Link & Tag Verification**
  - Run `ops/count_existing_inlinks.py` to confirm >= 4 inbound links per pillar.
  - Run `ops/check_all_tags.py` to confirm 0 malformed HTML tags.

- [ ] **Task 8: Inventory Update, Metadata Sync & Search Engine Syndication**
  - Run `ops/update_meta_inventory_stage87.py` to update `ops/meta_inventory.json` from 287 to 294 entries.
  - Run `ops/sync_visual_dates.py` to sync visual timestamps.
  - Run `ops/audit_internal_links.py` and `ops/audit_sitemap_lastmod.py`.
  - Run `ops/ping_search_engines.py` (IndexNow & WebSub).

- [ ] **Task 9: CI/CD Master Quality Gates**
  - Run `powershell -ExecutionPolicy Bypass -File scripts/verify-all-gates.ps1`
  - Run `powershell -ExecutionPolicy Bypass -File scripts/verify-guide-experience-public.ps1 -FixturesOnly`

- [ ] **Task 10: Commit, Push & Workspace Synchronization**
  - Commit all Stage 87 changes with conventional message.
  - Push to `origin/master`.
  - Sync `M:\Projects\vietnamguide`.
