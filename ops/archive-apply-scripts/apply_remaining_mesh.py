# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61 Part 2: Apply Remaining Mesh Operations for 250, 244, and 613
"""

import paramiko
import json

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

OPS = [
    {
        "post_id": 250,
        "slug": "quy-nhon-travel-guide",
        "target": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test coast value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>. Compare Quy Nhon against <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>, <a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a>, <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a>, and <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.</p>',
        "replacement": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then test coast value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>. Compare Quy Nhon against <a href="/destinations/nha-trang-travel-guide/">Nha Trang Travel Guide</a>, <a href="/compare/mui-ne-vs-nha-trang/">Mui Ne vs Nha Trang</a>, explore the wild southern headlands via the <a href="/destinations/phu-yen-travel-guide/">Phu Yen Travel Guide</a>, or compare with <a href="/destinations/da-nang-travel-guide/">Da Nang Travel Guide</a> and <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.</p>'
    },
    {
        "post_id": 244,
        "slug": "nha-trang-travel-guide",
        "target": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then compare beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, island value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and the beach fork in <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central beach chapter.</p>',
        "replacement": '<p>Start with the <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, then compare beach value in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a>, explore northern basalt cliffs via the <a href="/destinations/phu-yen-travel-guide/">Phu Yen Travel Guide</a>, island value in <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, and the beach fork in <a href="/compare/phu-quoc-vs-nha-trang/">Phu Quoc vs Nha Trang</a>. Use <a href="/compare/north-central-south-vietnam/">North vs Central vs South Vietnam</a>, <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a>, <a href="/plan/vietnam-evisa/">Vietnam E-Visa</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central beach chapter.</p>'
    },
    {
        "post_id": 613,
        "slug": "vietnam-train-travel",
        "target": '<p><strong>Take the train for medium-haul daylight scenery (especially the 3-hour Da Nang to Hue coastal cliff crossing) or overnight journeys between Hanoi and Hue/Da Nang (SE1/SE3).</strong> Rail travel protects your luggage security, lets you sleep flat in 4-berth air-conditioned cabins, and avoids airport transfer fatigue. <strong>Do not ride the entire 35-hour Hanoi to Saigon line in one continuous sitting</strong>; breaking the route into regional chapters (Hanoi &rarr; Ninh Binh &rarr; Hue &rarr; Da Nang &rarr; Quy Nhon) turns a grueling marathon into an enriching journey.</p>',
        "replacement": '<p><strong>Take the train for medium-haul daylight scenery (especially the 3-hour Da Nang to Hue coastal cliff crossing) or overnight journeys between Hanoi and Hue/Da Nang (SE1/SE3).</strong> Rail travel protects your luggage security, lets you sleep flat in 4-berth air-conditioned cabins, and avoids airport transfer fatigue. <strong>Do not ride the entire 35-hour Hanoi to Saigon line in one continuous sitting</strong>; breaking the route into regional chapters (Hanoi &rarr; Ninh Binh &rarr; Dong Hoi for <a href="/plan/phong-nha-cave-treks/">Phong Nha cave treks</a> &rarr; Hue &rarr; Da Nang &rarr; Quy Nhon &rarr; Tuy Hoa for the <a href="/destinations/phu-yen-travel-guide/">Phu Yen travel guide</a>) turns a grueling marathon into an enriching journey.</p>'
    }
]

def main():
    print("=== Applying Remaining Mesh for Posts 250, 244, and 613 ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/remaining_mesh_ops.json', 'w') as f:
        f.write(json.dumps(OPS, ensure_ascii=False, indent=2))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/remaining_mesh_ops.json');
$ops = json_decode($json_str, true);

$applied = 0;
$errors = 0;

foreach ($ops as $op) {
    $postId = (int)$op['post_id'];
    $slug = $op['slug'];
    $target = $op['target'];
    $replacement = $op['replacement'];

    $post = get_post($postId);
    if (!$post) {
        echo "[ERROR] Post ID {$postId} not found.\\n";
        $errors++;
        continue;
    }

    $content = $post->post_content;
    if (strpos($content, $target) === false) {
        echo "[FAIL TARGET] String not found in ID {$postId} ({$slug})\\n";
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

    $wpdb->update(
        $wpdb->posts,
        [
            'post_modified'     => '2026-09-21 08:30:00',
            'post_modified_gmt' => '2026-09-21 08:30:00'
        ],
        ['ID' => $postId]
    );

    echo "[APPLIED] ID {$postId} ({$slug})\\n";
    $applied++;
}

echo "\\nRemaining Mesh Summary: Applied {$applied}, Errors: {$errors}\\n";
"""

    with sftp.file('/tmp/apply_remaining_mesh.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/apply_remaining_mesh.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    ssh.exec_command("rm -f /tmp/apply_remaining_mesh.php /tmp/remaining_mesh_ops.json")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.close()

if __name__ == '__main__':
    main()
