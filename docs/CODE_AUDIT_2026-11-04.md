# Đánh giá mã nguồn VietnamGuide — 04/11/2026

VietnamGuide có nền tảng sản phẩm đáng kể: theme WordPress riêng, MU-plugin bảo vệ quy trình biên tập, 282 tuyến nội dung trong registry, sáu công cụ lập kế hoạch, bản đồ, photo dispatch, SEO/schema, PWA và nhiều lớp kiểm thử. Tuy nhiên, độ tin cậy của một số luồng chưa theo kịp độ rộng tính năng. Các lỗi bảo mật khi xuất HTML, ghi dữ liệu newsletter, khôi phục trạng thái và tương thích môi trường cần được xử lý trước đợt mở rộng tiếp theo.

Đây là đánh giá dựa trên mã nguồn và kết quả chạy mới trong working tree hiện tại. Báo cáo cũ không được dùng làm bằng chứng cho kết luận. Không chỉnh sửa mã ứng dụng, nội dung có sẵn, commit, push hoặc deploy trong lượt đánh giá này.

## Phạm vi và phương pháp

- Lập inventory và SHA-256 cho 104 file mã nguồn/cấu hình thực thi hiện có: 69.801 dòng, gồm 44 PHP, 32 Python, 12 PowerShell scripts, 1 PowerShell module, 5 JavaScript, 5 CSS, 2 HTML, 1 YAML và 2 file cấu hình/hook không có đuôi.
- Theo dõi các luồng từ entry point theme/MU-plugin đến routing, nội dung, context/cache, SEO, toolkits, biểu mẫu, PWA, compiler, deployer và quality gates. Đọc các test harness liên quan, không chỉ tên test hay kết quả pass.
- Kiểm tra toàn bộ 282 Markdown guides bằng compiler hiện có; kiểm tra parity giữa registry nguồn, registry trong theme và PHP được sinh ra.
- Tái hiện các nhánh lỗi với PHP và Chromium. Với lỗi ảnh, dùng lớp HTML API từ WordPress 6.8.3 chính thức thay cho lớp giả lập trong test của dự án.
- Inventory và kiểm tra cú pháp toàn bộ file không phải là chứng nhận đã kiểm chứng mọi nhánh hay mọi dữ kiện du lịch. Những khối dữ liệu sinh tự động được kiểm tra bằng parity/contract; các phát hiện bên dưới phân biệt rõ tái hiện thực thi, đọc code và rủi ro thiết kế.

Môi trường chính: PHP 8.3.33, Node 24.14.0, PowerShell 7.6.5; kiểm tra bổ sung bằng Windows PowerShell 5.1. Repo không chứa một WordPress core + database có thể khởi động hoàn chỉnh. Không xác nhận trạng thái plugin, database, cache, mail hay cấu hình thực tế trên production từ kết quả local này. Các bộ smoke test render component PHP dùng stub WordPress, không phải end-to-end qua WordPress đầy đủ.

## Kiến trúc quan sát từ code

| Lớp | Trách nhiệm thực tế | Nhận xét |
|---|---|---|
| `vietnamguide-core.php` | EEAT metadata, shortcode, ACF, bảo vệ cập nhật bằng WP-CLI, schema, affiliate links, hạn chế một số endpoint | Có guard và theo dõi manual drift; bảo vệ tập trung vào WP-CLI, không phải một lớp phân quyền chung cho mọi đường ghi |
| `functions.php`, template PHP, patterns, CSS/JS | Giao diện, enqueue, menu, trang guide, search, 404 | Phân module tốt hơn một theme nguyên khối; nhiều logic và dataset vẫn nằm trực tiếp trong PHP render |
| `guide-routing.php` | Registry, rollout, route classification/alias, search fallback, newsletter/contact | Một module đang kiêm cả routing và nghiệp vụ biểu mẫu |
| `guide-content.php`, `guide-context.php` | Tách hero/body, heading/TOC, Markdown fallback, context cache | Có validation cấu trúc và xử lý fallback; parser Markdown tự viết trùng với Python và có lỗi không tiến chỉ số |
| `guide-seo.php`, `guide-aio.php` | Title/description, robots, sitemap, JSON-LD, FAQ/HowTo, liên kết hành trình, ảnh, llms.txt, discovery pings | Lớp lớn nhất và nhiều quyền can thiệp; `guide-seo.php` có 4.440 dòng, khoảng 383 KB |
| Sáu toolkit + map/photo | Visa, cost, season, airport, packing, itinerary; bản đồ và ảnh | Tính năng có thật, phần lớn xử lý bằng JS ở browser; dữ liệu chính sách/giá/tỷ giá chủ yếu là hằng số |
| `ops/` | Compiler, registry contracts, linter, freshness sentinel, asset builder, deploy | Có nhiều kiểm tra, SSH trust, checksum, backup; còn khoảng cách giữa kiểm tra văn bản và bảo đảm hành vi |
| `ops/tests/`, `tests/` | Unit/contract, browser smoke, mutation, recovery/portability | Độ rộng tốt; hai nhóm test có harness/môi trường khác nhau và không đều nằm trong CI |

282 record đều có `status=published`, `template=guide`. Trường `current_template` vẫn ghi 195 `legacy` và 87 `guide`, trong khi `guide-rollout.php` bật rollout toàn registry. Đây là trạng thái metadata và cấu hình trong repo, không phải bằng chứng chỉ 87 trang hoặc toàn bộ 282 trang đang chạy đúng trên production.

Tất cả record hiện cùng `last_reviewed=2026-09-28`; `generated_at` cũng là ngày này. Việc sửa Markdown hôm nay không tự chứng minh rằng SEO date hoặc nội dung database đã được cập nhật.

## Kết quả kiểm chứng mới

| Kiểm tra | Kết quả |
|---|---|
| PHP lint, từng file | 44/44 không có lỗi cú pháp; gồm 41 runtime và 3 ops verifiers |
| JavaScript syntax | 5/5 không có lỗi cú pháp |
| Python AST parse | 32/32 không có lỗi cú pháp |
| PowerShell parser | 13/13 script/module không có lỗi cú pháp |
| `unittest discover -s ops/tests` | 322 test: 317 pass, 5 skip do chủ động tắt network tests |
| Playwright smoke trong bộ unittest | Đã chạy với Chromium, không bị skip vì thiếu browser |
| `ops/test-homepage-images.py` | 5/5 pass |
| Anti-AI Slop self-test | 38/38 pass; trùng một phần bộ unittest, không cộng thành số test duy nhất mới |
| Core MU-plugin, block patterns, homepage theme, guide experience verifiers | Pass ở checkout hiện tại |
| Compiler nội dung | 282/282 guides compile được |
| Registry compiler `--check` | Pass, parity ba artifact |
| Tariff/freshness sentinel với ngày 2026-11-04 | Pass: 282 routes, 12 clusters, 0 stale/invalid date theo quy tắc hiện có |
| Static deploy-security audit | Pass cho `deploy_theme_updates.py`; không phải penetration test |
| Recovery parser trên PowerShell 7.6.5 | **93/119 pass, 26 fail** |
| Recovery parser trên Windows PowerShell 5.1 | **119/119 pass** |
| MU-plugin LF/CRLF portability | **Fail** khi tạo mutation fixture CRLF; fixture LF pass |

Không chạy `verify-all-gates.ps1 -Full`: code của tùy chọn này bao gồm deploy production. Không chạy recovery baseline cần snapshot production ngoài repo. Không chạy các live/network tests đã skip, không gọi các endpoint ghi dữ liệu production.

## Phát hiện ưu tiên

P1: nên xử lý trước release tiếp theo. P2: lỗi ảnh hưởng chức năng/độ tin cậy cần có kế hoạch sửa gần. P3: mức ảnh hưởng nhỏ hơn hoặc nợ kỹ thuật. Mức ưu tiên không đồng nghĩa đã xảy ra sự cố production.

### F01 — P1 — JSON-LD normalization có thể làm script thoát ra HTML

**Đã tái hiện bằng PHP + Chromium.** Tại [guide-seo.php:3815](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:3815), code decode JSON, gọi `vg_schema_clean_data()` để giải mã HTML entity, rồi encode với `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`. Đoạn output không có bảo vệ phù hợp cho ngữ cảnh bên trong thẻ `<script>`.

Một description chứa chuỗi đã escape `&lt;/script&gt;&lt;script&gt;...` ban đầu không thực thi JavaScript. Sau normalization, browser thực thi đoạn script thử nghiệm vô hại `window.__auditExecuted=true`. Hàm được nối vào output của Rank Math tại priority 89/91 và filter output.

Điều đã chứng minh: phép biến đổi này không an toàn khi một trường đầu vào chứa nội dung như trên. Chưa chứng minh khách chưa đăng nhập có thể đưa chuỗi đó vào schema trên production. Phạm vi khai thác phụ thuộc quyền kiểm soát dữ liệu phía trước và plugin thực tế.

Hướng xử lý: encode an toàn cho script context, không biến HTML entity thành markup có thể đóng thẻ; thêm browser regression test cho cả `</script>` và dạng entity. Bằng chứng: `reproductions.json`.

### F02 — P1 — SEO filter ghi đè chỉ thị noindex

**Đã tái hiện ở filter.** [guide-seo.php:390](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:390) xóa `noindex`/`nofollow`, sau đó ép `index`/`follow` cho mọi trường hợp ngoài 404 và search. Input `index=noindex, follow=nofollow` trở thành `index=index, follow=follow`.

Điều này làm mất quyết định hạn chế index từ lớp phía trước, ví dụ thiết lập riêng của trang hoặc môi trường staging. Nhánh `wp_robots` cũng thêm `index` mà không xử lý xung đột với `noindex`. Không kết luận crawler đã index nội dung riêng tư; đây là lỗi output policy đã quan sát.

Hướng xử lý: giữ chỉ thị hạn chế có sẵn, chỉ bổ sung snippet controls cho route public đủ điều kiện, kiểm thử site privacy và protected content.

### F03 — P2 — Newsletter bỏ subscriber cũ sau 500 email

**Đã tái hiện.** [guide-routing.php:868](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php:868) cắt option còn 500 phần tử bằng `array_slice`. Sau 501 email khác nhau, storage còn 500, email đầu mất và email cuối còn.

Toàn bộ danh sách được ghi bằng read-modify-write trên một option; các request đồng thời còn có nguy cơ ghi đè nhau. Trong code được kiểm tra không thấy một subscriber store bền vững khác hoặc luồng unsubscribe hoàn chỉnh. Tái hiện dùng địa chỉ `.invalid` và stub option, không gửi mail.

Hướng xử lý: storage riêng có unique key cho email, ghi nguyên tử, trạng thái consent/unsubscribe và chính sách lưu giữ rõ ràng. Nếu 500 chỉ là log gần nhất, cần tách log khỏi dữ liệu subscription chính.

### F04 — P2 — Link chia sẻ không khôi phục đúng kế hoạch

**Đã tái hiện trong browser.** [guide-cost-calculator.php:741](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php:741) chỉ set param có giá trị, không xóa param rỗng. Tại dòng 791, route preset ghi đè days/style/party/flights sau khi đọc query.

- Chọn Ha Giang, đổi 7 ngày: URL chứa `days=7`, UI là **245 USD**. Reload cùng URL: trở lại **4 ngày, 140 USD**.
- Reset về 10 ngày vẫn giữ `route=ha-giang` trong URL; reload lại quay về 4 ngày.
- Số chuyến bay không được serialize vào URL như các field khác.
- [guide-visa-checker.php:666](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php:666) chỉ lưu nationality/days. Chọn multiple-entry rồi reload lại single-entry.

Hướng xử lý: định nghĩa đầy đủ schema state, dùng một thứ tự ưu tiên nhất quán, xóa param khi reset và kiểm tra round-trip serialize/deserialize. Bằng chứng: `toolkit-reproductions.json`.

### F05 — P2 — Copy ngân sách gọi hàm không tồn tại

**Đã tái hiện trong browser.** [guide-cost-calculator.php:1048](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php:1048) và các nhánh copy/share khác gọi `fallbackCopy()` nhưng file không định nghĩa hàm này. Khi Clipboard API không có, click copy phát sinh `ReferenceError: fallbackCopy is not defined`.

Hướng xử lý: một helper clipboard dùng chung, có xử lý cả unavailable/rejected/unsuccessful và thông báo đúng kết quả.

### F06 — P2 — Hai Markdown parser có vòng lặp không tiến

**Đã tái hiện ở cả Python và PHP.** [compile_guide_content.py:258](M:/Projects/vietnamguide/ops/compile_guide_content.py:258) và [guide-content.php:613](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-content.php:613) dừng paragraph khi dòng bắt đầu bằng `#` hoặc `|`, trong khi các nhánh heading/table đòi điều kiện chặt hơn. Dòng `#hashtag` hoặc `|incomplete table` không được nhánh nào tiêu thụ; chỉ số vòng lặp không tăng.

Mỗi input đều vượt timeout 3 giây và bị tiến trình kiểm tra chấm dứt. 282 file hiện tại vẫn compile pass; lỗi xuất hiện với input hợp lệ như đoạn văn bắt đầu bằng hashtag hoặc Markdown chưa hoàn chỉnh. PHP fallback có thể giữ worker cho đến timeout nếu gặp dữ liệu tương tự.

Hướng xử lý: bảo đảm mỗi vòng tiêu thụ ít nhất một dòng, thống nhất predicate nhận diện block; ưu tiên parser được kiểm thử theo chuẩn thay cho hai bản tự viết khác nhau.

### F07 — P2 — Reduced motion vô hiệu hóa chức năng không liên quan animation

**Đã tái hiện trong browser.** [homepage.js:72](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js:72) `return` khỏi toàn bộ IIFE nếu người dùng bật giảm chuyển động hoặc thiếu IntersectionObserver. Các handler phía sau cho back-to-top, copy link và phím `/` không được đăng ký.

Kiểm tra A/B với cùng DOM: `no-preference` sao chép/focus search được; `reduce` không được. Hướng xử lý: chỉ bỏ animation, luôn đăng ký các chức năng cơ bản.

### F08 — P2 — Tối ưu ảnh dùng sai hành vi HTML API; test stub che lỗi

**Đã tái hiện với lớp WordPress 6.8.3 chính thức.** [guide-seo.php:4127](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:4127) gọi `next_tag()` nhưng dùng `is_tag_closer()` để thoát hero. Mặc định bộ xử lý bỏ qua thẻ đóng, nên `$inHero` không được reset. Input gồm một hero và một ảnh body khiến **cả hai** nhận `loading=eager` và `fetchpriority=high`.

[Test stub tại test_guide_schema.py:95](M:/Projects/vietnamguide/ops/tests/test_guide_schema.py:95) trả cả thẻ mở lẫn đóng khi query null; đó không phải hành vi mặc định của WordPress. Đối chiếu [WordPress HTML API](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/next_tag/) và [mã nguồn phiên bản dùng để tái hiện](https://github.com/WordPress/WordPress/blob/6.8.3/wp-includes/html-api/class-wp-html-tag-processor.php).

Hướng xử lý cần xử lý đúng cấu trúc lồng nhau và void elements, không chỉ thêm `tag_closers=visit` rồi giữ nguyên bộ đếm hiện tại. Dùng test integration với HTML API thật. Bằng chứng: `image-repro.log`.

### F09 — P2 — AIO tìm Markdown sai thư mục

**Đã tái hiện.** [guide-aio.php:661](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-aio.php:661) dùng `dirname(__DIR__,4)` từ thư mục `inc`, ra `.../wordpress`, rồi tìm `wordpress/content/...`. Nội dung thực ở `.../vietnamguide/content/...`.

Với `plan/vietnam-in-july`, file nguồn có thật nhưng helper trả chuỗi rỗng. Phần heading enrichment trong `/llms-full.txt` không có dữ liệu từ file đó. Deployer hiện cũng không đưa thư mục content vào artifact, nên sửa số cấp thư mục chưa đủ để bảo đảm tính năng trên server. Cần cấu hình content root hoặc artifact riêng, có contract về sự hiện diện của dữ liệu.

### F10 — P2 — Bước PHP lint trên CI không bao phủ như tên gọi

**Xác nhận từ cấu hình và tài liệu PHP.** [.github/workflows/ci.yml:51](M:/Projects/vietnamguide/.github/workflows/ci.yml:51) dùng `find ... -exec php -l {} +`, trong khi CI chọn PHP 8.2. Theo [PHP CLI manual](https://www.php.net/manual/en/features.commandline.options.php), trước 8.3 chỉ hỗ trợ một filename cho `-l`. Cần gọi mỗi file một lần hoặc chọn phiên bản hỗ trợ nhiều file.

Các gate khác có thể phát hiện một số lỗi tương ứng, nhưng riêng bước lint này không bảo đảm đã kiểm tra toàn bộ PHP. Lượt audit hiện tại đã lint từng file riêng.

### F11 — P2 — Recovery/portability phụ thuộc phiên bản PowerShell và newline

**Đã chạy thực tế trên hai host.** Recovery parser pass 119/119 trên Windows PowerShell 5.1 nhưng fail 26/119 trên PowerShell 7.6.5. [RecoveryParser.psm1:2106](M:/Projects/vietnamguide/tests/lib/RecoveryParser.psm1:2106) và dòng 2359 dùng `-split "\n", -1` theo ý định chia mọi dòng. Trên PowerShell 7 ở máy này, mẫu ba dòng cho ra một phần tử; bỏ `-1` cho ra ba phần tử. Một số lỗi khác liên quan binding/contract của test và không được quy tất cả về một nguyên nhân duy nhất.

Portability test chạy fixture LF thành công, nhưng fixture CRLF fail vì phép `.Replace($AffiliateLoop, ...)` tại [verify-core-mu-plugin.ps1:531](M:/Projects/vietnamguide/ops/verify-core-mu-plugin.ps1:531) không tìm đúng đoạn multiline để tạo mutation. Đây là lỗi kiểm chứng fixture, chưa phải bằng chứng MU-plugin runtime hỏng với CRLF.

Hai script này không nằm trong workflow CI hiện tại. Hướng xử lý: xác định host được hỗ trợ, sửa ngữ nghĩa split và normalize newline trước mutation, rồi đưa kiểm tra phù hợp vào CI. Bằng chứng: bốn file log/JSON recovery và portability.

### F12 — P1 vận hành — `-Full` của quality gates có tác dụng deploy

**Xác nhận bằng đọc code, không thực thi.** [verify-all-gates.ps1:76](M:/Projects/vietnamguide/ops/verify-all-gates.ps1:76) dùng `if ($Deploy -or $Full)` để chạy deployer. Người vận hành muốn chạy kiểm tra đầy đủ có thể vô tình yêu cầu release nếu máy có cấu hình SSH.

Hướng xử lý: `-Full` chỉ mở rộng kiểm chứng; deploy phải có cờ riêng rõ ràng. Trong lượt đánh giá không gọi tùy chọn này.

### F13 — P2 thiết kế — Quyền sở hữu nội dung và SEO chưa thống nhất

**Xác nhận từ các nhánh code; chưa kiểm tra database production.** MU-plugin có cơ chế Admin-first, nhưng [guide-seo.php:469](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:469) lấy title/description registry để ghi đè output SEO mà không xét `vg_content_owner` hay `vg_automation_lock`. Test hiện tại còn yêu cầu registry phải thắng giá trị database.

Sitemap `lastmod` đồng bộ từ `last_reviewed`, trong khi toàn bộ 282 giá trị vẫn là 2026-09-28. Nội dung, title Admin, Markdown và schema có thể khác nhau mà không phát sinh lỗi cú pháp hoặc parity registry.

Deployer chỉ đưa code/assets trong allowlist lên server. Compiler Markdown hiện xuất HTML/kiểm tra; không có bước nhập WordPress database trong luồng release đó. Vì vậy không thể xem “282 file compile pass” là “282 bài đã lên site”.

Hướng xử lý: định nghĩa nguồn có thẩm quyền cho từng trường, đồng bộ có dấu vết và review date thật, kiểm tra output HTML/schema/sitemap so với đúng nguồn được chọn.

### F14 — P2 nội dung kỹ thuật — Schema quảng bá tính năng khác implementation

**Xác nhận trực tiếp từ code.** [guide-seo.php:3704](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php:3704) mô tả cost calculator có bốn tier và tỷ giá “real-time”. [guide-cost-calculator.php:396](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php:396) có ba key trong `RATES` và tỷ giá là hằng số trong JavaScript. Không có nguồn tỷ giá trực tiếp trong luồng này.

Đây là lệch giữa schema và sản phẩm, không phải kết luận tỷ giá cụ thể đang sai. Hướng xử lý: sinh feature metadata từ cùng dữ liệu với UI, ghi rõ rate benchmark và ngày kiểm tra, không hứa cập nhật trực tiếp khi code không làm điều đó.

### F15 — P3 — Một click in checklist gọi print hai lần

**Đã tái hiện.** Packing widget có click handler riêng gọi `window.print()`, đồng thời [guide-experience.js:5](M:/Projects/vietnamguide/wordpress/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js:5) bắt sự kiện bubbling từ `[data-vg-print]`. Khi cả hai script được nạp, một click làm bộ đếm stub `window.print` tăng hai lần. Cần một nơi quản lý hành vi in hoặc chặn xử lý lặp.

## Nhận xét về chất lượng, hiệu năng và vận hành

Điểm tốt đáng giữ: phân chia module nền tảng, safe rendering ở nhiều output HTML, nonce và rate limiting của biểu mẫu, kiểm tra route contracts, fallback cho nội dung không khớp guide shell, asset hash, ảnh responsive homepage, keyboard navigation ở nhiều widget, xác minh host key SSH, checksum và backup trước deploy. Những điều này là cơ sở để sửa tiếp, không cần viết lại toàn dự án.

Các vấn đề cấu trúc cần xử lý sau lỗi chức năng:

1. **SEO module làm quá nhiều việc.** Metadata, schema, ảnh, table enhancement, travel datasets, discovery pings và PWA cùng nằm trong một file. Nên tách theo trách nhiệm và kiểm thử từng boundary.
2. **Dữ liệu du lịch được lặp ở nhiều nơi.** Markdown, route descriptions, FAQ/HowTo, toolkit arrays, homepage và AIO dễ lệch. `tariff_sentinel.py` kiểm tra date và token/regex; `content_depth_auditor.py` đếm số liệu; anti-slop linter đo từ/cấu trúc câu. Các chỉ số này không truy xuất và đối chiếu giá, luật hoặc lịch vận hành với nguồn chính thức. Cần bản ghi dữ kiện có source URL, thời điểm kiểm tra, thời gian hiệu lực và nơi sử dụng.
3. **CSS nền tảng đang nặng.** `homepage.css` 8.922 dòng, 182.619 byte; tổng bốn CSS trong assets là 247.436 byte chưa nén. Homepage CSS được enqueue rộng trên site. Đây là số đo source, chưa phải transfer size qua CDN hoặc điểm Core Web Vitals.
4. **Build tối ưu chưa nối vào runtime.** `build-theme-assets.py` tạo `.min.css/.min.js`, nhưng enqueue và deploy allowlist vẫn tham chiếu file gốc. Minifier regex cũng không phải parser bảo toàn đầy đủ ngữ nghĩa JS/CSS. Nên đo trước, tách tải theo tính năng và dùng toolchain phù hợp.
5. **Deploy chưa atomic theo release.** Tarball được kiểm tra checksum nhưng từng file được `mv` lần lượt. Có khoảng thời gian một request có thể nhìn thấy file thuộc hai phiên bản. Đây là rủi ro thiết kế, chưa tái hiện outage. Nên stage toàn release, health check rồi chuyển phiên bản đồng bộ, với rollback thực sự được thử.
6. **PWA cần kiểm tra hành trình offline thật.** Precache dùng URL asset không query, enqueue dùng hash version; một trang/toolkit chưa từng tải đủ asset trước khi offline cần được kiểm thử riêng. Chưa kết luận mọi hành trình offline đều lỗi.
7. **Chỉ số test đang khuyến khích kiểm tra hình thức.** Nhiều test đối chiếu text/token và stub cùng giả định với implementation. Chúng vẫn hữu ích cho regression nhưng không đủ bảo đảm hành vi framework, bảo mật HTML và state chia sẻ.

## Thứ tự xử lý đề xuất

1. Chặn script breakout; giữ đúng noindex; tách `-Full` khỏi deploy. Acceptance: browser không thực thi payload, chỉ thị hạn chế không bị mất, kiểm tra đầy đủ không mở kết nối deploy.
2. Sửa subscriber storage, parser không tiến, state URL, clipboard, reduced motion và ảnh. Acceptance: tái hiện trong evidence chuyển từ lỗi sang hành vi đúng; thêm test ở đúng boundary gây lỗi.
3. Bổ sung WordPress integration thật cho HTML API, Rank Math output, Admin-first, sitemap, REST/forms và object cache. Sửa CI lint, PowerShell portability; không bỏ qua test lỗi để làm dashboard xanh.
4. Chốt quyền sở hữu từng nguồn nội dung và đường đưa Markdown lên site. Giảm dữ liệu trùng; phân biệt review timestamp, publish timestamp và build timestamp.
5. Đo performance trên WordPress gần production, sau đó tối ưu CSS/ảnh, chia nhỏ SEO/toolkit, nối build artifact vào release. Kiểm tra offline, rollback và HTTP health checks theo hành trình thực tế.

Không nên tăng thêm số lượng bài hoặc nhồi thêm schema chỉ để tăng chỉ số nội bộ trước khi các luồng trên đáng tin cậy hơn. Nền tảng hiện có đủ tốt để cải thiện theo từng lớp; phần cần đầu tư nhất là tính đúng đắn của output, dữ liệu và khả năng kiểm chứng khi vận hành.

## Hồ sơ bằng chứng

Evidence ở [docs/audits/2026-11-04-code-audit](M:/Projects/vietnamguide/docs/audits/2026-11-04-code-audit). Các file chính:

- `source-inventory.json`: file, dòng, byte, SHA-256, function/test inventory; không phải coverage report.
- `unittest.log`, `syntax.log`, các gate logs: kết quả chạy mới.
- `reproductions.json`: noindex, newsletter, JSON-LD browser execution, Python Markdown, reduced motion.
- `toolkit-reproductions.json`: copy fallback, cost reset/share, visa share, print.
- `reproduce_browser.py` và `browser-reproductions.json`: script tái hiện đã chạy lại thành công; gọi bằng `python -X utf8 docs/audits/2026-11-04-code-audit/reproduce_browser.py` từ repo. Chỉ render local/stub, chặn network browser và không gửi mail.
- `php-markdown-repro.json`, `image-repro.log`, `aio-repro.json`: lỗi parser PHP, ảnh với HTML API thật, đường dẫn AIO.
- `recovery-parser.log`, `recovery-parser-windows-powershell.log`, `recovery-failures.json`, `portability.log`: phân biệt môi trường và kiểu thất bại.

Các phát hiện liên quan khả năng khai thác production, hiệu năng thực tế và chính sách du lịch cần phép kiểm tra tương ứng; không suy từ local green tests thành cam kết bảo mật, SEO hay độ chính xác dữ kiện.
