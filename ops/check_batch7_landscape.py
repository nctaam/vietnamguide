# -*- coding: utf-8 -*-
import urllib.request
import urllib.parse
import json
import sys

sys.stdout.reconfigure(encoding='utf-8')

test_candidates = {
    518: ['Nhà thờ đá Sa Pa.jpg', 'Sapa view 3.jpg', 'Sapa market.jpg', 'Sapa in winter.JPG'],
    601: ['A boat on the Thu Bon River, Hoi An, Vietnam.jpg', 'Hoi An - HoiAn1441.jpg', 'Lanterns in Hoi An.jpg'],
    525: ['Ta Van Muong Ha vallei.jpg', 'Ta Van Muong Ha vallei (84346).jpg', 'Vietnam-Sapa-Y Linh Ho village-P1070238.jpg']
}

def get_meta(fn):
    url = f"https://commons.wikimedia.org/w/api.php?action=query&titles=File:{urllib.parse.quote(fn)}&prop=imageinfo&iiprop=size|extmetadata&iiurlwidth=1280&format=json"
    req = urllib.request.Request(url, headers={'User-Agent': 'VietnamGuideBot/1.0'})
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        p = list(data['query']['pages'].values())[0]
        if 'imageinfo' not in p: return None
        ii = p['imageinfo'][0]
        em = ii.get('extmetadata', {})
        w = ii.get('thumbwidth', 0)
        h = ii.get('thumbheight', 0)
        return w, h, em.get('Artist', {}).get('value', ''), em.get('LicenseShortName', {}).get('value', '')
    except Exception as e:
        return None

for pid, flist in test_candidates.items():
    print(f"\nTarget {pid}:")
    for fn in flist:
        res = get_meta(fn)
        if res:
            w, h, art, lic = res
            r = round(w / h, 2) if h else 0
            print(f"  {fn} -> {w}x{h} (r={r}) | {lic}")
