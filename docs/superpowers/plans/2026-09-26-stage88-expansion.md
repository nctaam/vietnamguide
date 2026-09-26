# Stage 88 Implementation Plan: 7 High-Intent Pillars Expansion (294 → 301 Pages)

> **For agentic workers:** REQUIRED SUB-SKILL: Follow the proven 10-step content & mesh pipeline. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Author, validate, deploy, and link 7 high-intent travel pillars across northern ethnic market hubs, Cao Bang mountain transit corridors, Central Highland tea plateaus, and offshore Con Dao express ferry routes, elevating total published pages from 294 to 301.

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

## 7 Target Pillars for Stage 88
1. `where-to-stay-in-bac-ha` (Parent ID: 7 `destinations`):
   - Focus KW: `where to stay in Bac Ha`
   - Meta Title: `Where to Stay in Bac Ha (2026): Town vs Village Stays` (53 chars)
   - Meta Desc: `Where to stay in Bac Ha: town center hotels vs ethnic Hmong homestays with 2026 room rates, Sunday market advice, and Ban Pho village lodging.` (144 chars)

2. `where-to-stay-in-bao-lac` (Parent ID: 7 `destinations`):
   - Focus KW: `where to stay in Bao Lac`
   - Meta Title: `Where to Stay in Bao Lac (2026): Mountain Transit Stays` (55 chars)
   - Meta Desc: `Where to stay in Bao Lac: riverside town guesthouses vs Black Lo Lo homestays with 2026 room rates, QL34 loop logistics, and mountain route advice.` (149 chars)

3. `where-to-stay-in-bao-loc` (Parent ID: 7 `destinations`):
   - Focus KW: `where to stay in Bao Loc`
   - Meta Title: `Where to Stay in Bao Loc (2026): Tea Hills vs Town Center` (57 chars)
   - Meta Desc: `Where to stay in Bao Loc: central commercial hotels vs tea plantation eco-lodges with 2026 room prices, Dambri waterfall tips, and highland advice.` (149 chars)

4. `sapa-to-bac-ha-transport` (Parent ID: 6 `plan`):
   - Focus KW: `Sapa to Bac Ha transport`
   - Meta Title: `Sapa to Bac Ha Transport (2026): Bus, Tour & Motorbike` (54 chars)
   - Meta Desc: `Sapa to Bac Ha transport: Sunday market shuttle buses, local minibuses via Lao Cai, and private car hires. 2026 ticket fares, schedules, and route tips.` (154 chars)

5. `tran-de-to-con-dao-ferry` (Parent ID: 6 `plan`):
   - Focus KW: `Tran De to Con Dao ferry`
   - Meta Title: `Tran De to Con Dao Ferry (2026): Superdong Speedboat` (52 chars)
   - Meta Desc: `Tran De to Con Dao ferry: Superdong fast speedboat schedule, ticket fares, Soc Trang pier logistics, sea conditions, and island boarding advice.` (146 chars)

6. `vung-tau-to-con-dao-ferry` (Parent ID: 6 `plan`):
   - Focus KW: `Vung Tau to Con Dao ferry`
   - Meta Title: `Vung Tau to Con Dao Ferry (2026): Express Catamaran` (51 chars)
   - Meta Desc: `Vung Tau to Con Dao ferry: Con Dao Express 36 fast catamaran timetables, ticket prices, Cau Da pier transfers, and open-ocean crossing advice.` (143 chars)

7. `pleiku-to-quy-nhon-transport` (Parent ID: 6 `plan`):
   - Focus KW: `Pleiku to Quy Nhon transport`
   - Meta Title: `Pleiku to Quy Nhon Transport (2026): Bus vs Private Car` (55 chars)
   - Meta Desc: `Pleiku to Quy Nhon transport: direct limousine buses and private transfers via QL19. 2026 ticket fares, An Khe pass travel times, and road advice.` (148 chars)

---

## Execution Tasks

- [ ] **Task 1: Author Content Definition Files**
  - Create `ops/content_where_to_stay_in_bac_ha.py`
  - Create `ops/content_where_to_stay_in_bao_lac.py`
  - Create `ops/content_where_to_stay_in_bao_loc.py`
  - Create `ops/content_sapa_to_bac_ha_transport.py`
  - Create `ops/content_tran_de_to_con_dao_ferry.py`
  - Create `ops/content_vung_tau_to_con_dao_ferry.py`
  - Create `ops/content_pleiku_to_quy_nhon_transport.py`

- [ ] **Task 2: Content QA & Slop Linter Suite**
  - Create and run `ops/test_stage88_content.py`
  - Verify 100/100 HLS score, 0 clichés, evidence count >= 15, and SERP length limits.

- [ ] **Task 3: Production VPS Deployment**
  - Create and run `ops/deploy_stage88_pillars.py`
  - Verify HTTP 200, concierge verdict block, comparison tables, and titles for all 7 live URLs.

- [ ] **Task 4: Candidate Link Targets Fetch**
  - Create and run `ops/fetch_stage88_mesh_targets.py`
  - Pull live content for 28 candidate host posts into `ops/stage88_mesh_targets.json`.

- [ ] **Task 5: Mesh Replacement Assembly & Testing**
  - Create `ops/test_mesh88_candidates.py` and verify all replacements match exactly once (`count == 1`).
  - Create and run `ops/assemble_stage88_mesh.py` to produce `ops/stage88_mesh_updates.json`.

- [ ] **Task 6: Production Mesh Deployment**
  - Create and run `ops/apply_stage88_mesh.py`
  - Purge LiteSpeed cache.

- [ ] **Task 7: Inbound Link & Tag Verification**
  - Run `ops/count_existing_inlinks.py` to confirm >= 4 inbound links per pillar.
  - Run `ops/check_all_tags.py` to confirm 0 malformed HTML tags.

- [ ] **Task 8: Inventory Update, Metadata Sync & Search Engine Syndication**
  - Run `ops/update_meta_inventory_stage88.py` to update `ops/meta_inventory.json` from 294 to 301 entries.
  - Run `ops/sync_visual_dates.py` to sync visual timestamps.
  - Run `ops/audit_internal_links.py` and `ops/audit_sitemap_lastmod.py`.
  - Run `ops/ping_search_engines.py` (IndexNow & WebSub).

- [ ] **Task 9: CI/CD Master Quality Gates**
  - Run `powershell -ExecutionPolicy Bypass -File ops/verify-all-gates.ps1`
  - Run `powershell -ExecutionPolicy Bypass -File ops/verify-guide-experience-public.ps1 -FixturesOnly`

- [ ] **Task 10: Commit, Push & Workspace Synchronization**
  - Commit all Stage 88 changes with conventional message.
  - Push to `origin/master`.
  - Sync `M:\Projects\vietnamguide`.
