# VietnamGuide — Kế hoạch hoàn thiện dự án

- **Ngày lập:** 2026-09-28
- **Trạng thái:** In progress — baseline, route contract, routing adapter, AIO adapter, rollout manifest and deploy-path security gate implemented; full migration pending
- **Baseline:** `master` / `a7a86f6`
- **Mục tiêu khuyến nghị:** hoàn thiện guide experience cho toàn bộ 282 route nội dung đang có trên sitemap

## Mục tiêu

Đưa dự án từ trạng thái production-capable lên production-ready có kiểm soát ở quy mô toàn site. Kết quả cuối cùng phải có một nguồn route duy nhất, template được áp dụng nhất quán, AIO/SEO đồng bộ, CI kiểm tra đúng production surface, deploy có quyền tối thiểu và rollback được, đồng thời các request quảng cáo/analytics tuân theo consent policy.

Kế hoạch này giả định 282 route nội dung thuộc `destinations`, `plan`, `compare` và `itineraries` đều được sử dụng guide shell. Nếu quyết định giữ 87 route pilot, phải dừng tại cổng quyết định đầu tiên và cập nhật toàn bộ AIO, verifier, sitemap contract và tài liệu sản phẩm để mô tả rõ 195 route legacy.

## Baseline đã xác nhận

- Inventory nội bộ: 301 entries.
- Public page sitemap: 302 URL.
- Route-shaped content pages: 282.
- Guide shell hiện tại: 87/282 route.
- Legacy shell hiện tại: 195/282 route.
- Stage 88 đã kiểm tra: 7/7 route trả HTTP 200 nhưng thiếu guide shell.
- 282 route đã crawl live: đều HTTP 200, một H1, title, description, canonical, JSON-LD và không có `noindex`.
- Git baseline sạch tại commit `a7a86f6`; các thay đổi adapter và contract hiện đang ở working tree, chưa deploy.
- Snapshot kiểm kê có thể tái sử dụng tại [route baseline](baselines/2026-09-28-route-baseline.json).
- Registry đầu tiên có 282 route tại [route_registry.json](../ops/route_registry.json), có `route_count` và governance metadata cho từng record, được kiểm tra bằng [route_contract.py](../ops/route_contract.py).
- Chênh lệch hiện tại giữa registry và hai route list hardcode được ghi tại [route drift report](baselines/2026-09-28-route-drift.json).
- Theme đã chứa bản sao registry tại `inc/guide-route-registry.json`; PHP routing adapter đọc dữ liệu này nhưng vẫn giữ allowlist 87 route làm runtime contract trong giai đoạn tương thích.
- AIO adapter đọc cùng registry để tạo inventory 282 route đã publish; inventory literal 87 route vẫn là fallback khi registry lỗi hoặc không có.
- Deploy path chính đã có cấu hình bắt buộc qua environment, known-hosts verification, non-root mặc định, per-file rollback backup, dry-run và purge cache explicit tại [deploy_theme_updates.py](../ops/deploy_theme_updates.py); artifact cũng upload route registry cùng PHP adapter để rollout không bị rơi về pilot do thiếu JSON. Các script deploy lịch sử khác chưa được phép dùng cho release mới cho đến khi chuyển cùng contract.
- Static audit hiện ghi nhận 179 ứng viên trong `ops/` sau khi loại archive, backup, test và chính file scanner; chỉ [deploy_theme_updates.py](../ops/deploy_theme_updates.py) đủ điều kiện release. 177 ứng viên legacy còn findings, trong đó 177 script có host, key, root hoặc host-key trust/config pattern không đạt; báo cáo đầy đủ nằm tại [deploy security audit](baselines/2026-09-29-deploy-security-audit.json). Đây là inventory di trú, không phải lý do để đưa các script cũ vào release path.

## Phạm vi

### Bao gồm

- Canonical route registry và route contract.
- Theme routing, guide context và migration của 195 route legacy.
- `/llms.txt`, `/llms-full.txt`, sitemap consistency và freshness metadata.
- Consent cho AdSense, GA4 và QR request.
- Deployment security, secret management, staging, canary và rollback.
- CI, test discovery, PHP/JS/Python syntax và public route smoke tests.
- Service worker, cache policy, security headers và observability.

### Không bao gồm

- Thay thế WordPress core.
- Viết lại toàn bộ nội dung khi nội dung hiện tại đã đạt cấu trúc yêu cầu.
- Di chuyển database/uploads ngoài các backup hoặc export cần cho migration.
- Thay đổi nhà cung cấp quảng cáo/analytics.
- Redesign toàn bộ brand hoặc layout ngoài phạm vi guide-shell consistency.

## Kế hoạch thực hiện

### 1. Đóng băng baseline và chốt contract

**Ước tính:** 0,5–1 ngày. **Phụ thuộc:** không.

- Lưu sitemap, route counts, `meta_inventory.json`, HTML mẫu của 87 pilot và 7 Stage 88.
- Tạo danh sách ngoại lệ: hub, toolkit, privacy, terms và utility page.
- Chọn contract cuối cùng: 282 route sitewide hoặc 87 route pilot.
- Ghi owner và tiêu chí Go/No-Go cho từng loại route.

**Hoàn thành khi:** có tài liệu scope được review, expected counts cố định và chưa có migration production nào chạy trước khi scope được chốt.

### 2. Tạo canonical route registry

**Ước tính:** 1–2 ngày. **Phụ thuộc:** bước 1.

Tạo registry máy đọc được chứa tối thiểu:

`path`, `type`, `title`, `description`, `status`, `template`, `parent`, `last_reviewed`, `source`, `content_owner`.

Registry phải là nguồn duy nhất cho:

- `vg_is_guide_experience_page()` và guide routing.
- AIO route inventory.
- Public verifier.
- Sitemap/AIO consistency test.
- Báo cáo rollout và freshness.

**Hoàn thành khi:** registry có 282 route hợp lệ theo contract, không duplicate path, không path sai format và có test phát hiện route orphan/unknown.

**Tiến độ hiện tại:** registry ops/theme và contract test đã hoàn thành; cần nối verifier, sitemap contract và routing runtime làm nguồn duy nhất.

Routing đã có chế độ rollout có kiểm soát trong [guide-routing.php](../wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php): mặc định không khai báo `VG_GUIDE_REGISTRY_ROLLOUT` nên 87 route pilot vẫn giữ nguyên; staging có thể đặt constant này thành mảng path để mở một batch cụ thể, hoặc `true` để mở toàn bộ 282 record có target template `guide`. Xóa constant là rollback về pilot. Mọi batch phải được kiểm tra bằng route contract, public smoke và full crawl trước khi chuyển batch kế tiếp.

Danh sách batch deterministic được lưu trong [rollout-batches.json](baselines/2026-09-28-rollout-batches.json): 195 route legacy được chia thành 50, 50 và 95. Contract validator kiểm tra batch id/order, template nguồn/đích, count, duplicate path, unknown path và coverage toàn bộ pending set.

### 3. Chuyển theme routing và migrate route legacy

**Ước tính:** 3–5 ngày. **Phụ thuộc:** bước 2.

- Thay allowlist 87 path trong [guide-routing.php](../wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php) bằng lookup registry.
- Giữ legacy fallback trong giai đoạn chuyển tiếp để migration có thể rollback.
- Cập nhật [page.php](../wordpress/wp-content/themes/vietnamguide-premium/page.php) để dùng template theo registry và giữ ngoại lệ hub/tool rõ ràng.
- Migrate 195 route legacy theo batch 50 / 50 / 95 trong [rollout manifest](baselines/2026-09-28-rollout-batches.json).
- Chỉ thêm content adapter cho page thiếu hero, verdict, table hoặc metadata; không chỉnh tay hàng loạt nếu không cần.

**Hoàn thành khi:** mọi route thuộc target contract có đúng template, một H1, guide hero, navigation/TOC, assets hợp lệ, fragment targets hợp lệ và không duplicate heading ID.

### 4. Đồng bộ AIO và SEO contract

**Ước tính:** 1–2 ngày. **Phụ thuộc:** bước 2; có thể chạy song song bước 3.

- Sinh `/llms.txt` và `/llms-full.txt` từ registry.
- Loại bỏ số lượng `87 Guides` hardcode khi contract là sitewide.
- Tách route guide khỏi hub/tool page.
- Thêm `last_reviewed`, `source` và trạng thái freshness cho claim nhạy cảm.
- Thêm test: registry route set = sitemap route set = AIO route set sau khi loại ngoại lệ.

**Tiến độ hiện tại:** AIO đã có adapter registry và fallback an toàn; `llms-full.txt` lấy số lượng từ inventory thực tế. Việc xóa inventory hardcode và đóng contract sitemap/AIO vẫn chờ sau khi chốt target template.

**Hoàn thành khi:** chênh lệch registry/sitemap/AIO bằng 0 ngoài các ngoại lệ được khai báo; 7 Stage 88 slug xuất hiện đúng một lần trong AIO.

### 5. Thiết lập content freshness governance

**Ước tính:** 1–2 ngày ban đầu; sau đó chạy định kỳ. **Phụ thuộc:** bước 2.

Bổ sung source, ngày review, ngày review tiếp theo và owner cho visa, entry requirements, fees, ferry, transport, airport và weather claims. Rà soát [guide-visa-checker.php](../wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php) và các claim trong [guide-aio.php](../wordpress/wp-content/themes/vietnamguide-premium/inc/guide-aio.php).

**Hoàn thành khi:** record quá hạn tạo cảnh báo, claim không có nguồn không được gắn trạng thái “verified”, và có owner xác nhận các nội dung pháp lý.

### 6. Sửa consent, analytics và third-party requests

**Ước tính:** 2–4 ngày. **Phụ thuộc:** phải xong trước release monetization tiếp theo.

- Phân loại necessary, analytics, advertising và third-party utility.
- Chặn AdSense trong [header.php](../wordpress/wp-content/themes/vietnamguide-premium/header.php) trước consent phù hợp.
- Chặn GA4 trong [guide-analytics.php](../wordpress/wp-content/themes/vietnamguide-premium/inc/guide-analytics.php) trước consent hoặc triển khai Consent Mode được kiểm thử.
- Thêm Reject/Manage Settings vào [footer.php](../wordpress/wp-content/themes/vietnamguide-premium/footer.php).
- Localize QR generation hoặc loại bỏ dữ liệu query/PII khỏi request tới dịch vụ ngoài.

**Hoàn thành khi:** browser test xác nhận không có AdSense, GA4 hoặc QR request trước consent; opt-out có hiệu lực và privacy policy khớp hành vi thực tế.

### 7. Chuẩn hóa deployment và rollback

**Ước tính:** 3–5 ngày. **Phụ thuộc:** phải hoàn thành trước batch production tiếp theo.

- Hợp nhất deploy script thành một CLI/wrapper có review.
- Di chuyển host, port, user, key path và webroot sang secret/environment configuration.
- Dùng deploy user tối thiểu quyền; bỏ `--allow-root` nếu có thể.
- Thay `paramiko.AutoAddPolicy()` bằng pinned `known_hosts`/`RejectPolicy`.
- Rotate deploy key sau migration.
- Thêm dry-run, staging target, deploy lock, backup, artifact hash và post-deploy health check.
- Diễn tập rollback bằng artifact version trước khi migrate content.

**Hoàn thành khi:** static scan không còn hardcoded production credential/path hoặc `AutoAddPolicy()` trong production path; staging deploy và rollback đều pass; log có commit ID, actor, artifact hash và kết quả.

**Tiến độ hiện tại:** deploy path chính đã loại bỏ host/IP/key/root hardcode và `AutoAddPolicy()`, yêu cầu `VG_DEPLOY_*`, kiểm tra known_hosts bằng `RejectPolicy`, hỗ trợ `--dry-run` và chỉ purge cache khi truyền `--purge-cache`. Các script legacy còn hardcode được giữ ngoài release path và cần được hợp nhất hoặc archive trước khi Go.

Static audit chạy bằng [deploy_security_audit.py](../ops/deploy_security_audit.py), được gọi bởi Gate 8 trong [verify-all-gates.ps1](../ops/verify-all-gates.ps1) và CI. Gate strict chỉ đánh giá release path đã khai báo; legacy findings vẫn được lưu thành artifact để theo dõi di trú và không bị hiểu nhầm là đã an toàn. Khi upload thực tế, deployer tạo rollback backup theo từng file dưới `wp-content/.vietnamguide-deployment-backups/<timestamp-id>/` trước khi mở SFTP upload.

Required configuration: `VG_DEPLOY_HOST`, `VG_DEPLOY_PORT`, `VG_DEPLOY_USER`, `VG_DEPLOY_KEY`, `VG_DEPLOY_KNOWN_HOSTS` và `VG_DEPLOY_ROOT`. `VG_DEPLOY_ALLOW_ROOT=1` chỉ dùng cho exception đã được phê duyệt. Preflight chạy `python ops/deploy_theme_updates.py --dry-run`; upload thực tế không purge cache nếu thiếu `--purge-cache`.

### 8. Mở rộng CI và sửa test discovery

**Ước tính:** 2–4 ngày. **Phụ thuộc:** bước 2; có thể chạy song song bước 7.

Cập nhật [.github/workflows/ci.yml](../.github/workflows/ci.yml) và [verify-all-gates.ps1](../ops/verify-all-gates.ps1) để chạy:

- PHP lint toàn bộ `wordpress/**/*.php`, bao gồm MU-plugin.
- JavaScript syntax check.
- Python compile với warning.
- Registry/sitemap/AIO contract test.
- Guide mutation suite theo PR hoặc nightly.
- Public route smoke trong scheduled job riêng.

Đổi tên test file có dấu gạch nối hoặc chuẩn hóa sang pytest để test discovery bao phủ toàn bộ test suite. Đồng bộ local orchestrator, CI và pre-commit; route contract đã được thêm vào cả hai đường chạy.

**Hoàn thành khi:** test discovery phát hiện số lượng test lớn hơn 0, MU-plugin được lint trong CI, route drift làm CI fail và public live check có artifact kết quả.

**Tiến độ hiện tại:** CI đã lint toàn bộ cây `wordpress/`, kiểm tra cú pháp JavaScript/Python, chạy test discovery, route/AIO contract test, deployment configuration safety test và deployment surface static security audit; local orchestrator đã có Gate 8 tương ứng. Các test legacy có tên file dấu gạch nối và public scheduled smoke vẫn cần chuẩn hóa riêng.

### 9. Hạn chế cache, service worker và security headers

**Ước tính:** 2–4 ngày. **Phụ thuộc:** sau frontend smoke test.

Cập nhật [sw.js](../wordpress/sw.js):

- Chỉ cache public HTML và immutable assets.
- Bypass auth cookie, `Cache-Control: private`, request có state và query động.
- Bump cache version khi content/template thay đổi.
- Có invalidation và rollback test.

Thiết lập `Cache-Control` rõ ràng cho HTML/assets. Xây dựng CSP theo hai bước Report-Only rồi Enforce sau khi inventory external scripts hoàn tất; giữ nguyên HSTS, `nosniff`, frame protection, referrer và permissions policy.

**Hoàn thành khi:** không có private/authenticated HTML trong cache, service worker update/rollback pass và CSP không làm hỏng ads, analytics hoặc interactive tools.

### 10. Canary release, monitoring và runbook

**Ước tính:** 2–3 ngày cho release đầu tiên. **Phụ thuộc:** các P1 đã hoàn thành.

Trình tự release:

1. Deploy registry và verifier lên staging.
2. Migrate 50 route.
3. Chạy static, browser và public smoke test.
4. Kiểm tra sitemap/AIO diff.
5. Theo dõi error rate và asset failure.
6. Migrate 50 route tiếp theo.
7. Migrate phần còn lại.
8. Crawl lại toàn bộ 282 route.
9. Chốt release và cập nhật runbook.

Theo dõi tối thiểu: HTTP 4xx/5xx, guide coverage, sitemap/AIO drift, canonical/schema failure, asset 404, consent errors, service worker errors và deploy failure.

**Hoàn thành khi:** canary ổn định trong thời gian quan sát đã thống nhất, full crawl pass, rollback path đã được chứng minh và [RECOVERY.md](RECOVERY.md) phản ánh quy trình mới.

## Go/No-Go gates

### No-Go

- Registry và sitemap không khớp.
- Có route 404/500 hoặc fatal error.
- Guide coverage thấp hơn target đã chốt.
- Thiếu H1, canonical hoặc JSON-LD.
- Public smoke test thất bại.
- Có request analytics/advertising trước consent.
- Còn `AutoAddPolicy()` hoặc root deploy không được phê duyệt.
- Test discovery trả về 0.
- Rollback staging chưa được diễn tập.

### Go

- 282/282 route đạt target template theo contract.
- 282/282 route HTTP 200, một H1, title, description, canonical và JSON-LD hợp lệ.
- AIO, sitemap và registry không có drift ngoài ngoại lệ được khai báo.
- Static gates, mutation tests và browser smoke đều pass.
- Staging rollback pass.
- Canary khỏe và có audit trail đầy đủ.

## Definition of Done

- [ ] Scope 282 route sitewide hoặc 87 route pilot được phê duyệt và ghi rõ.
- [ ] Có canonical route registry.
- [ ] Không còn ba route list hardcode độc lập.
- [ ] Tất cả route thuộc target contract dùng đúng template.
- [ ] AIO, sitemap và registry đồng bộ.
- [ ] Claim nhạy cảm có source, review date và owner.
- [ ] Consent chặn đúng request non-essential.
- [ ] Deploy không dùng hardcoded production secret/path hoặc host-key auto trust.
- [ ] CI lint MU-plugin, discover test, chạy contract test và public scheduled smoke.
- [ ] Service worker không cache private/stateful response.
- [ ] Canary, rollback và recovery runbook đã được kiểm chứng.
- [ ] Git working tree sạch sau release và artifact release đã được ghi nhận.

## Quyết định cần chốt

1. Sản phẩm chọn target 282 route sitewide hay giữ 87 route pilot?
2. Production hiện có staging clone và rollback snapshot đủ gần với database/cache không?
3. Consent policy áp dụng cho toàn bộ traffic hay ưu tiên EEA/UK trước?

Khuyến nghị mặc định cho implementation là target 282 route, staging-first, canary theo batch và prior consent cho analytics/advertising.
