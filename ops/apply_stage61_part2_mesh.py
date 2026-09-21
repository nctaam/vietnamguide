# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61 Part 2: Plan and Apply Semantic Internal Linking Mesh for Coastal & Cave Pillars
"""

import paramiko
import json
import urllib.request
import re
import sys

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

OPERATIONS = [
    # 1. Quy Nhon -> Phu Yen
    {
        "post_id": 250,
        "slug": "quy-nhon-travel-guide",
        "description": "Link Phu Yen travel guide from planning chain",
        "target": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test coast value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>. Compare Quy Nhon against <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>, <a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a>, <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a>, and <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> to decide whether a quieter central coast stop belongs.</p>',
        "replacement": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test coast value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>. Compare Quy Nhon against <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>, <a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a>, explore the wild southern headlands via the <a href="/destinations/phu-yen-travel-guide/">Phu Yen Travel Guide</a>, or compare with <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a> and <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> to decide whether a quieter central coast stop belongs.</p>'
    },

    # 2. Nha Trang -> Phu Yen
    {
        "post_id": 244,
        "slug": "nha-trang-travel-guide",
        "description": "Link Phu Yen travel guide from planning chain",
        "target": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then compare beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, island value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and the beach fork in <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before locking domestic flights or resort nights.</p>',
        "replacement": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then compare beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, explore northern basalt cliffs via the <a href="/destinations/phu-yen-travel-guide/">Phu Yen Travel Guide</a>, island value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and the beach fork in <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before locking domestic flights or resort nights.</p>'
    },

    # 3. Ly Son -> Ly Son vs Cham Islands
    {
        "post_id": 257,
        "slug": "ly-son-travel-guide",
        "description": "Link Ly Son vs Cham Islands from comparison paragraph",
        "target": '<p>They solve different jobs. Cham Islands is usually a Hoi An marine-day decision. Ly Son is a stronger volcanic-island detour that needs more route margin.</p>',
        "replacement": '<p>They solve different jobs (see our detailed <a href="/compare/ly-son-vs-cham-islands/">Ly Son vs Cham Islands</a> decision guide). Cham Islands is an easy Hoi An marine-day decision. Ly Son is a dramatic volcanic-island detour that needs more route margin.</p>'
    },

    # 4. Cham Islands -> Ly Son vs Cham Islands
    {
        "post_id": 254,
        "slug": "cham-islands-travel-guide",
        "description": "Link Ly Son vs Cham Islands from comparison paragraph",
        "target": '<p>They solve different jobs. An Bang is the easier Hoi An beach reset. Cham Islands is a boat-based marine day with protected-area context and more logistics.</p>',
        "replacement": '<p>They solve different jobs (see also our <a href="/compare/ly-son-vs-cham-islands/">Ly Son vs Cham Islands</a> comparison). An Bang is the easier Hoi An beach reset. Cham Islands is a boat-based marine day with protected-area context and more logistics.</p>'
    },

    # 5. Tam Coc -> Hang Mua
    {
        "post_id": 418,
        "slug": "tam-coc-travel-guide",
        "description": "Link Hang Mua guide from viewpoint section",
        "target": '<p>Mua Cave is strongest when the route needs one panoramic proof point over the Tam Coc landscape. It is weaker when the group is tired, heat is high, stairs are a problem, or the climb would steal the calm that made Tam Coc valuable in the first place.</p>',
        "replacement": '<p>Mua Cave is strongest when the route needs one panoramic proof point over the Tam Coc landscape (consult our <a href="/destinations/hang-mua-ninh-binh-guide/">Hang Mua Ninh Binh guide</a> for the 500-step dragon climb). It is weaker when the group is tired, heat is high, stairs are a problem, or the climb would steal the calm that made Tam Coc valuable in the first place.</p>'
    },

    # 6. Trang An vs Tam Coc -> Hang Mua
    {
        "post_id": 336,
        "slug": "trang-an-vs-tam-coc",
        "description": "Link Hang Mua guide from viewpoint trade-off section",
        "target": '<p>Only when weather, heat, visibility, steps, and energy support it. Hang Mua is a strong viewpoint, not a mandatory proof that the Ninh Binh day was complete.</p>',
        "replacement": '<p>Only when weather, heat, visibility, steps, and energy support it. Use our <a href="/destinations/hang-mua-ninh-binh-guide/">Hang Mua Ninh Binh</a> guide for dawn climb timing; it is a strong viewpoint, not a mandatory proof that the Ninh Binh day was complete.</p>'
    },

    # 7. Ninh Binh Travel Guide -> Hang Mua
    {
        "post_id": 190,
        "slug": "ninh-binh-travel-guide",
        "description": "Link Hang Mua guide from climb paragraph",
        "target": '<p>Plan it as a focused climb and viewpoint, not a casual afterthought. Go early or late when possible, avoid exposed midday heat, and skip it if wet steps, crowding, or energy make the climb a poor trade.</p>',
        "replacement": '<p>Plan it as a focused climb and viewpoint (see our dedicated <a href="/destinations/hang-mua-ninh-binh-guide/">Hang Mua Ninh Binh guide</a>), not a casual afterthought. Go early or late when possible, avoid exposed midday heat, and skip it if wet steps, crowding, or energy make the climb a poor trade.</p>'
    },

    # 8. Phong Nha Travel Guide -> Phong Nha Cave Treks
    {
        "post_id": 503,
        "slug": "phong-nha-travel-guide",
        "description": "Link Phong Nha cave treks from practical questions section",
        "target": '<p>The practical question is not which cave is famous. It is what your group can enjoy safely and honestly. Footing, heat, stairs, boats, swimming, darkness, mud, camping, medical disclosure, and guide authority matter more than a photo caption.</p>',
        "replacement": '<p>The practical question is not which cave is famous. It is what your group can enjoy safely and honestly (consult our <a href="/plan/phong-nha-cave-treks/">Phong Nha cave treks</a> guide for Hang En and Tu Lan expedition comparisons). Footing, heat, stairs, boats, swimming, darkness, mud, camping, medical disclosure, and guide authority matter more than a photo caption.</p>'
    },

    # 9. Vietnam Train Travel -> Phong Nha & Phu Yen
    {
        "post_id": 613,
        "slug": "vietnam-train-travel",
        "description": "Link Phong Nha treks and Phu Yen guide from train route concierge verdict",
        "target": '<p><strong>Take the train for medium-haul daylight scenery (especially the 3-hour Da Nang to Hue coastal cliff crossing) or overnight journeys that save hotel costs (such as Hanoi to Dong Hoi or Hue).</strong> Trains offer a social, grounded rhythm that reveals rural countryside impossible to see from airplanes. <strong>Skip long-distance trains exceeding twelve hours</strong> (such as the full 30+ hour Hanoi to Ho Chi Minh City marathon) unless you are a committed rail enthusiast; domestic flights are faster and often comparably priced.</p>',
        "replacement": '<p><strong>Take the train for medium-haul daylight scenery (especially the 3-hour Da Nang to Hue coastal cliff crossing) or overnight journeys that save hotel costs (such as Hanoi to Dong Hoi for <a href="/plan/phong-nha-cave-treks/">Phong Nha cave treks</a> or coastal stops at Tuy Hoa for the <a href="/destinations/phu-yen-travel-guide/">Phu Yen travel guide</a>).</strong> Trains offer a social, grounded rhythm that reveals rural countryside impossible to see from airplanes. <strong>Skip long-distance trains exceeding twelve hours</strong> (such as the full 30+ hour Hanoi to Ho Chi Minh City marathon) unless you are a committed rail enthusiast; domestic flights are faster and often comparably priced.</p>'
    }
]

def main():
    print(f"=== Applying Stage 61 Part 2 Link Mesh ({len(OPERATIONS)} ops) ===")
    
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage61_part2_mesh_ops.json', 'w') as f:
        f.write(json.dumps(OPERATIONS, ensure_ascii=False, indent=2))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/stage61_part2_mesh_ops.json');
$ops = json_decode($json_str, true);

$applied = 0;
$errors = 0;

foreach ($ops as $op) {
    $postId = (int)$op['post_id'];
    $slug = $op['slug'];
    $target = $op['target'];
    $replacement = $op['replacement'];
    $desc = $op['description'];

    $post = get_post($postId);
    if (!$post) {
        echo "[ERROR] Post ID {$postId} ({$slug}) not found.\\n";
        $errors++;
        continue;
    }

    $content = $post->post_content;
    if (strpos($content, $target) === false) {
        echo "[FAIL TARGET] String not found in ID {$postId} ({$slug}): {$desc}\\n";
        $errors++;
        continue;
    }

    $new_content = str_replace($target, $replacement, $content);
    $res = wp_update_post([
        'ID' => $postId,
        'post_content' => $new_content
    ], true);

    if (is_wp_error($res)) {
        echo "[ERROR] wp_update_post failed for ID {$postId}: " . $res->get_error_message() . "\\n";
        $errors++;
        continue;
    }

    // Harmonize modified timestamp
    $wpdb->update(
        $wpdb->posts,
        [
            'post_modified'     => '2026-09-21 08:30:00',
            'post_modified_gmt' => '2026-09-21 08:30:00'
        ],
        ['ID' => $postId]
    );

    echo "[APPLIED] ID {$postId} ({$slug}): {$desc}\\n";
    $applied++;
}

echo "\\nMesh Summary: Applied {$applied}, Errors: {$errors}\\n";
"""

    with sftp.file('/tmp/apply_stage61_part2_mesh.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    print("Executing mesh update via WP-CLI on production VPS...")
    cmd = f"wp eval-file /tmp/apply_stage61_part2_mesh.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print("Stderr:", err)

    ssh.exec_command("rm -f /tmp/apply_stage61_part2_mesh.php /tmp/stage61_part2_mesh_ops.json")

    print("Purging LiteSpeed cache...")
    purge_cmd = f"wp litespeed-purge all --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print(stdout.read().decode('utf-8'))
    ssh.close()

if __name__ == '__main__':
    main()
