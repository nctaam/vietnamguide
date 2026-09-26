# Stage 85 Implementation Plan: High-Intent Geographic Expansion (273 → 280 Pages)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand VietnamGuide's comprehensive coverage from 273 to 280 published pages by authoring, testing, deploying, and cross-linking 7 high-intent travel pillars across Northern mountain loops, Central Highland pine retreats, Mekong delta border transit, and interprovincial cave/highland corridors.

**Architecture:** WordPress CMS on LiteSpeed Enterprise VPS (`66.42.48.146:2209`), WP-CLI batch evaluation, Rank Math SEO metadata, strict parent-child routing hierarchy (`destinations` parent 7, `plan` parent 6), and a bidirectional internal link mesh with at least 4 verified incoming links per new pillar.

**Tech Stack:** WordPress, PHP, Python 3, Paramiko (SSH/SFTP), LiteSpeed Web Server, IndexNow, WebSub.

**Spec:** "Tối ưu nội dung, không thêm chức năng mới" — Zero new plugins, widgets, shortcodes, or schema changes. Strict adherence to Anti-AI Slop v3.0, 100/100 HLS, 0 Tier 1 clichés, and real 2026 price/timing evidence anchors.

## Global Constraints
- Strictly zero new plugins, shortcodes, or database schema modifications.
- Every authored article must achieve 100/100 HLS score and 0 Tier 1 clichés on `anti_ai_slop_engine_v3.py`.
- Evidence Density Index (EDI): minimum 15 explicit VND + USD price pairs and named travel entities per pillar.
- SERP metadata: Titles 40–60 characters (with 2026 freshness year), Meta Descriptions 130–155 characters.
- Link Mesh Guarantee: Every new pillar must receive at least 4 verified live inbound links from existing host posts.
- Sitewide E-E-A-T timestamps synchronized to September 26, 2026.
- All 5 automated CI/CD Quality Gates must pass before Git commit and push.

---

## 7 Proposed New Pillars (Stage 85)

1. **`where-to-stay-in-du-gia`** (Parent 7, Destinations)
   - Focus: Traditional Tay wooden stilt homestays vs quiet valley lodges along the Du Gia waterfall stream on the Ha Giang Loop.
   - Title: `Where to Stay in Du Gia (2026): Waterfall Homestays` (51 chars)
   - Meta Desc: `Where to stay in Du Gia: traditional Tay wooden stilt houses vs quiet riverside valley homestays near the waterfall with realistic 2026 rates and meal tips.` (154 chars)
   - Focus Keyword: `Where to Stay in Du Gia`

2. **`where-to-stay-in-mang-den`** (Parent 7, Destinations)
   - Focus: Pine forest wooden chalets vs town center boutique hotels and Pa Sy waterfall eco-lodges in Kon Tum's high-altitude plateau.
   - Title: `Where to Stay in Mang Den (2026): Pine Forest Stays` (51 chars)
   - Meta Desc: `Where to stay in Mang Den: pine forest wooden chalets vs town center boutique hotels and Pa Sy waterfall eco-lodges with 2026 room rates and climate tips.` (153 chars)
   - Focus Keyword: `Where to Stay in Mang Den`

3. **`nha-trang-to-da-lat-transport`** (Parent 6, Plan)
   - Focus: Shared limousine vans vs tourist coaches climbing Khanh Le Pass (Omega Pass) from coastal Khanh Hoa to highland Lam Dong.
   - Title: `Nha Trang to Da Lat Transport (2026): Bus vs Van` (48 chars)
   - Meta Desc: `Nha Trang to Da Lat transport: shared limousine vans vs tourist buses climbing Khanh Le Pass. 2026 ticket fares, schedules, and mountain road advice.` (149 chars)
   - Focus Keyword: `Nha Trang to Da Lat Transport`

4. **`ninh-binh-to-phong-nha-transport`** (Parent 6, Plan)
   - Focus: Direct overnight sleeper buses vs daytime trains to Dong Hoi with onward taxi shuttles to Phong Nha National Park.
   - Title: `Ninh Binh to Phong Nha Transport (2026): Bus vs Train` (53 chars)
   - Meta Desc: `Ninh Binh to Phong Nha transport: direct overnight sleeper buses vs trains to Dong Hoi with local taxi transfers. 2026 ticket fares, schedules, and advice.` (153 chars)
   - Focus Keyword: `Ninh Binh to Phong Nha Transport`

5. **`cao-bang-to-ba-be-transport`** (Parent 6, Plan)
   - Focus: Local passenger minivans vs private cars along Highway 34 and 279 connecting Ban Gioc / Cao Bang City with Ba Be Lake.
   - Title: `Cao Bang to Ba Be Transport (2026): Bus vs Car` (47 chars)
   - Meta Desc: `Cao Bang to Ba Be Lake transport: local passenger buses vs private cars via Highway 34 and 279. 2026 road conditions, transfer times, and mountain routes.` (153 chars)
   - Focus Keyword: `Cao Bang to Ba Be Transport`

6. **`ho-chi-minh-city-to-chau-doc-transport`** (Parent 6, Plan)
   - Focus: Futa express coaches vs VIP limousine minivans connecting Saigon with the Cambodian river border gateway of Chau Doc.
   - Title: `Ho Chi Minh to Chau Doc Transport (2026): Bus & Van` (51 chars)
   - Meta Desc: `Ho Chi Minh City to Chau Doc transport: Futa express buses vs limousine minivans. 2026 ticket fares, schedules, pickup points, and Cambodia boat advice.` (152 chars)
   - Focus Keyword: `Ho Chi Minh City to Chau Doc Transport`

7. **`where-to-stay-in-an-giang`** (Parent 7, Destinations)
   - Focus: Chau Doc riverfront hotels vs Sam Mountain panoramic resorts and Long Xuyen floating market transit bases.
   - Title: `Where to Stay in An Giang (2026): Chau Doc vs Long Xuyen` (56 chars)
   - Meta Desc: `Where to stay in An Giang: Chau Doc riverfront hotels vs Sam Mountain retreats and Long Xuyen floating market bases with 2026 room rates and travel advice.` (154 chars)
   - Focus Keyword: `Where to Stay in An Giang`

---

## 10-Task Execution Pipeline

### Task 1: Content Authoring (7 Content Modules)
**Files:**
- Create: `ops/content_where_to_stay_in_du_gia.py`
- Create: `ops/content_where_to_stay_in_mang_den.py`
- Create: `ops/content_nha_trang_to_da_lat_transport.py`
- Create: `ops/content_ninh_binh_to_phong_nha_transport.py`
- Create: `ops/content_cao_bang_to_ba_be_transport.py`
- Create: `ops/content_ho_chi_minh_city_to_chau_doc_transport.py`
- Create: `ops/content_where_to_stay_in_an_giang.py`

- [ ] **Step 1.1**: Author `ops/content_where_to_stay_in_du_gia.py` with HLS 100/100, zero clichés, real VND/USD rates.
- [ ] **Step 1.2**: Author `ops/content_where_to_stay_in_mang_den.py` with HLS 100/100, zero clichés, real VND/USD rates.
- [ ] **Step 1.3**: Author `ops/content_nha_trang_to_da_lat_transport.py` with HLS 100/100, zero clichés, Khanh Le Pass details.
- [ ] **Step 1.4**: Author `ops/content_ninh_binh_to_phong_nha_transport.py` with HLS 100/100, zero clichés, sleeper coach schedules.
- [ ] **Step 1.5**: Author `ops/content_cao_bang_to_ba_be_transport.py` with HLS 100/100, zero clichés, mountain road advice.
- [ ] **Step 1.6**: Author `ops/content_ho_chi_minh_city_to_chau_doc_transport.py` with HLS 100/100, zero clichés, Cambodia border advice.
- [ ] **Step 1.7**: Author `ops/content_where_to_stay_in_an_giang.py` with HLS 100/100, zero clichés, Chau Doc vs Long Xuyen bases.

---

### Task 2: Automated Quality Validation
**Files:**
- Create: `ops/test_stage85_content.py`

- [ ] **Step 2.1**: Define automated unit test checking SERP bounds, focus keyword exact presence, HLS >= 95, 0 Tier 1 clichés, and evidence anchor counts >= 15.
- [ ] **Step 2.2**: Run `python ops/test_stage85_content.py` and confirm 7/7 PASSED.

---

### Task 3: Production VPS Deployment
**Files:**
- Create: `ops/deploy_stage85_pillars.py`

- [ ] **Step 3.1**: Script automated WP-CLI post insertion/update on remote VPS (`66.42.48.146:2209`).
- [ ] **Step 3.2**: Execute deployment, verify HTTP 200, compare tables, and concierge verdicts on all 7 URLs.

---

### Task 4: Mesh Target Post Extraction
**Files:**
- Create: `ops/fetch_stage85_mesh_targets.py`
- Output: `ops/stage85_mesh_targets.json`

- [ ] **Step 4.1**: Query and download live HTML of ~30 candidate host posts from VPS database.
- [ ] **Step 4.2**: Verify JSON structure and availability of all host posts.

---

### Task 5: Candidate String Verification & Mesh Assembly
**Files:**
- Create: `ops/test_mesh85_candidates.py`
- Create: `ops/assemble_stage85_mesh.py`
- Output: `ops/stage85_mesh_updates.json`

- [ ] **Step 5.1**: Draft contextual anchor strings guaranteeing at least 4 inbound links per new pillar.
- [ ] **Step 5.2**: Run `python ops/test_mesh85_candidates.py` to confirm every target substring has exact match count = 1.
- [ ] **Step 5.3**: Run `python ops/assemble_stage85_mesh.py` to produce `ops/stage85_mesh_updates.json`.

---

### Task 6: Apply Mesh & Purge Cache
**Files:**
- Create: `ops/apply_stage85_mesh.py`

- [ ] **Step 6.1**: Push updated post_content via WP-CLI with `VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1`.
- [ ] **Step 6.2**: Purge LiteSpeed cache sitewide.

---

### Task 7: Inbound Link & Tag Verification
**Files:**
- Run: `ops/count_existing_inlinks.py`
- Run: `ops/check_all_tags.py`

- [ ] **Step 7.1**: Confirm all 7 pillars have >= 4 verified inbound links in database.
- [ ] **Step 7.2**: Confirm 0 malformed HTML tags across the entire site.

---

### Task 8: Discovery, Inventory & E-E-A-T Date Sync
**Files:**
- Create: `ops/update_meta_inventory_stage85.py`
- Run: `ops/sync_visual_dates.py`
- Run: `ops/audit_internal_links.py`
- Run: `ops/audit_sitemap_lastmod.py`
- Run: `ops/ping_search_engines.py`

- [ ] **Step 8.1**: Update `ops/meta_inventory.json` from 273 to 280 entries.
- [ ] **Step 8.2**: Synchronize visual and E-E-A-T timestamps to September 26, 2026.
- [ ] **Step 8.3**: Audit internal links (0 content orphans, total internal links > 2,420).
- [ ] **Step 8.4**: Audit sitemap (280 fresh URLs, 0 stale).
- [ ] **Step 8.5**: Dispatch IndexNow (280 URLs) and WebSub pings (2 hubs).

---

### Task 9: CI/CD Quality Gates & Regressions
**Files:**
- Run: `powershell -ExecutionPolicy Bypass -File .\ops\verify-all-gates.ps1`
- Run: `powershell -ExecutionPolicy Bypass -File .\ops\verify-guide-experience-public.ps1 -FixturesOnly`

- [ ] **Step 9.1**: Pass Gate 1 (Anti-AI Slop Engine v3.0, 34/34 tests).
- [ ] **Step 9.2**: Pass Gate 2 (Core MU-Plugin Invariant & Safety Contracts).
- [ ] **Step 9.3**: Pass Gate 3 (Gutenberg Block Patterns).
- [ ] **Step 9.4**: Pass Gate 4 (Theme CSS & Structure).
- [ ] **Step 9.5**: Pass Gate 5 (Interactive Shortcodes & A11y, 26/26 tests).
- [ ] **Step 9.6**: Pass public verifier fixtures.

---

### Task 10: Documentation & Git Workspace Synchronization
**Files:**
- Modify: `task.md` (artifact)
- Modify: `walkthrough.md` (artifact)

- [ ] **Step 10.1**: Record Stage 85 completion in `task.md` and `walkthrough.md`.
- [ ] **Step 10.2**: Stage and commit all changes: `git commit -m "feat(stage85): deploy 7 high-intent pillars, expand internal link mesh to 280 live pages"`.
- [ ] **Step 10.3**: Push to `origin/master`.
- [ ] **Step 10.4**: Pull sync secondary workspace `M:\Projects\vietnamguide`.
