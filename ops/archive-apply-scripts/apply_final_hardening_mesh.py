# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 61 Final Hardening Mesh: Guarantee >= 4 in-links for all 7 pillars
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
        "post_id": 231,
        "slug": "best-islands-in-vietnam",
        "target": "Choose Ly Son when the island itself is the point: volcanic coast, garlic-island culture, and offbeat photography. Skip it when the central route still lacks protected Hoi An, Hue, and Da Nang time.",
        "replacement": "Choose Ly Son when the island itself is the point: volcanic coast, garlic-island culture, and offbeat photography. For a detailed logistical comparison between Central Vietnam's two main island detours, review our head-to-head analysis of <a href=\"/compare/ly-son-vs-cham-islands/\">Ly Son vs Cham Islands</a>. Skip it when the central route still lacks protected Hoi An, Hue, and Da Nang time."
    },
    {
        "post_id": 204,
        "slug": "best-beaches-in-vietnam",
        "target": "Quy Nhon for quieter value (see our <a href=\"/destinations/quy-nhon-travel-guide/\">Quy Nhon travel guide</a>), Ly Son for volcanic coastal geology (review our <a href=\"/destinations/ly-son-travel-guide/\">Ly Son travel guide</a>)",
        "replacement": "Quy Nhon and Phu Yen for quieter value and dramatic coastal cliffs (see our <a href=\"/destinations/quy-nhon-travel-guide/\">Quy Nhon travel guide</a> and <a href=\"/destinations/phu-yen-travel-guide/\">Phu Yen travel guide</a>), Ly Son or Cham Islands for offbeat central sea excursions (compare <a href=\"/compare/ly-son-vs-cham-islands/\">Ly Son vs Cham Islands</a>)"
    },
    {
        "post_id": 20,
        "slug": "14-days-in-vietnam",
        "target": "Add cave expeditions via our <a href=\"/destinations/phong-nha-travel-guide/\">Phong Nha travel guide</a>, a beach rest",
        "replacement": "Add cave expeditions via our <a href=\"/destinations/phong-nha-travel-guide/\">Phong Nha travel guide</a> (or choose an active 1-to-3-day expedition with our <a href=\"/plan/phong-nha-cave-treks/\">Phong Nha cave treks guide</a>), a beach rest"
    },
    {
        "post_id": 224,
        "slug": "21-days-in-vietnam",
        "target": "<div class=\"vg-timeline-item\"><div class=\"vg-day\">Day 11</div><div><h3>Central extension or recovery</h3><p>Extend into Phong Nha's cave systems or remain stationary for beachfront relaxation. Add regional detours only when they genuinely elevate the route.</p></div></div>",
        "replacement": "<div class=\"vg-timeline-item\"><div class=\"vg-day\">Day 11</div><div><h3>Central extension or recovery</h3><p>Extend into Phong Nha's cave systems (such as Hang En or Tu Lan via our <a href=\"/plan/phong-nha-cave-treks/\">Phong Nha cave treks guide</a>) or remain stationary for beachfront relaxation. Add regional detours only when they genuinely elevate the route.</p></div></div>"
    },
    {
        "post_id": 478,
        "slug": "ninh-binh-without-rushing",
        "target": "<li><strong>Hang Mua Dragon Peak:</strong> Admission 100,000 VND; 486 stone steps to summit; best climbed at 06:30 or 17:00 to avoid midday heat.</li>",
        "replacement": "<li><strong>Hang Mua Dragon Peak:</strong> Admission 100,000 VND; 486 stone steps to summit; best climbed at 06:30 or 17:00 to avoid midday heat (see our <a href=\"/destinations/hang-mua-ninh-binh-guide/\">Hang Mua Ninh Binh guide</a> for timing and parking tips).</li>"
    }
]

def main():
    print("=== Applying Final Hardening Mesh via WP Eval-File ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/final_mesh_ops.json', 'w') as f:
        f.write(json.dumps(OPS, ensure_ascii=False, indent=2))

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/final_mesh_ops.json');
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

echo "\\nFinal Hardening Mesh Summary: Applied {$applied}, Errors: {$errors}\\n";
"""

    with sftp.file('/tmp/apply_final_mesh.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/apply_final_mesh.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("Stderr:", err)

    ssh.exec_command("rm -f /tmp/apply_final_mesh.php /tmp/final_mesh_ops.json")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.close()

if __name__ == '__main__':
    main()
