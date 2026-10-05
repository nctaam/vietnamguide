import json

sources = json.load(open('ops/stage69_mesh_sources.json', encoding='utf-8'))
with open(r'C:\Users\NCTaam\.gemini\antigravity\brain\99dde344-4f04-4d86-a026-48cc6e470e1b\scratch\all_tails_69.txt', 'w', encoding='utf-8') as out:
    for slug in sorted(sources.keys()):
        item = sources[slug]
        content = item['content']
        out.write(f"\n==================== {slug} (ID: {item['ID']}) ====================\n")
        out.write(content[-800:] if len(content) > 800 else content)
        out.write("\n")

print("Saved all tails to scratch/all_tails_69.txt")
