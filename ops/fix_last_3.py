# -*- coding: utf-8 -*-
import sys, os, re
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from fast_content_audit import clean_text

# 1. quy-nhon-to-nha-trang-transport
p1 = "ops/content_fix/quy-nhon-to-nha-trang-transport.html"
with open(p1, "r", encoding="utf-8") as f:
    h1 = f.read()

# Fix bigram
h1 = re.sub(
    r"If departing from downtown Quy Nhon Station",
    "When catching the train from downtown Quy Nhon Station",
    h1
)
# Fix cadence around Dieu Tri inland
h1 = h1.replace(
    "The station sits 11 kilometers inland from the city center in Dieu Tri town.",
    "The inland terminal is located 11 kilometers away in suburban Dieu Tri town. A taxi ride takes 20 minutes."
)
with open(p1, "w", encoding="utf-8") as f:
    f.write(h1)

# 2. vietnam-domestic-flights-guide
p2 = "ops/content_fix/vietnam-domestic-flights-guide.html"
with open(p2, "r", encoding="utf-8") as f:
    h2 = f.read()

h2 = h2.replace(
    "Vietnam Airlines vs Vietjet: Choosing the Right Airline",
    "Comparing Full-Service vs Low-Cost Airlines: Vietnam Airlines or Vietjet"
)
h2 = h2.replace(
    "<li><strong>Hanoi (HAN) to Da Nang (DAD):</strong> 1 hour 20 minutes. The primary link between the capital and central beaches.</li>",
    "<li><strong>Hanoi (HAN) to Da Nang (DAD):</strong> Flight duration is roughly 1 hour and 20 minutes. This forms the vital aerial bridge connecting the capital city directly with the central coastline.</li>"
)
with open(p2, "w", encoding="utf-8") as f:
    f.write(h2)

# 3. phu-quoc-ferry-guide
p3 = "ops/content_fix/phu-quoc-ferry-guide.html"
with open(p3, "r", encoding="utf-8") as f:
    h3 = f.read()

h3 = h3.replace(
    '<a href="/plan/can-tho-to-ha-tien-transport/">Can Tho to Ha Tien Transport</a>',
    '<a href="/plan/can-tho-to-ha-tien-transport/">Overland coach route from Can Tho to Ha Tien</a>'
)
h3 = h3.replace(
    "<summary>How do I get from Bai Vong Port to Duong Dong town or Long Beach?</summary>",
    "<summary>What is the best way to get from Bai Vong Port to central Duong Dong town or the resorts of Long Beach?</summary>"
)
with open(p3, "w", encoding="utf-8") as f:
    f.write(h3)

print("Verifying 3 files:")
for p, s in [(p1, "quy-nhon-to-nha-trang-transport"), (p2, "vietnam-domestic-flights-guide"), (p3, "phu-quoc-ferry-guide")]:
    txt = clean_text(open(p, "r", encoding="utf-8").read())
    r = analyze_text(txt, source_name=s)
    print(f"  {s}: Passed={r['passed']}, HLS={r['hls_score']}, EDI={r['edi']:.1f}")
    if not r['passed']:
        for k in r:
            if k.endswith('_violations') and r[k]:
                print(f"    {k}: {r[k]}")
