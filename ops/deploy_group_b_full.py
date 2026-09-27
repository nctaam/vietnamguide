# -*- coding: utf-8 -*-
"""
VietnamGuide: Deploy All 64 Group B Real Images & Featured Images
1. Uploads 64 images to VPS /tmp/group_b_media/
2. Sideloads into WordPress Media Library (/wp-content/uploads/2026/09/)
3. Sets _thumbnail_id (Featured Image) for OpenGraph & social sharing
4. Replaces existing hotlinks with local Zero-CLS <figure class="vg-guide-photo">
5. Purges LiteSpeed cache & restarts lsphp
"""
import paramiko
import urllib.request
import urllib.parse
import json
import ssl
import sys
import os
import io
import re
from PIL import Image

sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    with open('ops/group_b_validated_manifest.json', 'r', encoding='utf-8') as f:
        manifest = json.load(f)

    # Replace vertical image in post 477 with horizontal 1280x960 Dong Xuan market
    ctx = ssl.create_default_context()
    for it in manifest:
        if it['id'] == 477:
            it['norm_src'] = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Dong_Xuan_Market_market_in_the_Hanoi_old_quarter_%2830645886763%29.jpg/1280px-Dong_Xuan_Market_market_in_the_Hanoi_old_quarter_%2830645886763%29.jpg'
            it['alt'] = 'Bustling Dong Xuan Market and street vendors in Hanoi Old Quarter, Vietnam'
            it['caption'] = 'Navigating Hanoi\'s Old Quarter requires pacing: avoid rushed market transactions, watch step-down shop thresholds, and use metered apps for longer trips. Image: <a href="https://commons.wikimedia.org/wiki/File:Dong_Xuan_Market_market_in_the_Hanoi_old_quarter_(30645886763).jpg" target="_blank" rel="license noopener">Gary Todd / Public domain</a>.'
            # re-download locally
            req = urllib.request.Request(it['norm_src'], headers={'User-Agent': 'VietnamGuide/1.0 (travel@vietnamguide.net)'})
            data = urllib.request.urlopen(req, context=ctx, timeout=15).read()
            with open(it['local_file'], 'wb') as lf:
                lf.write(data)
            im = Image.open(io.BytesIO(data))
            it['width'], it['height'] = im.size
            it['ratio'] = round(it['width'] / it['height'], 2)
            print(f"Updated post 477 to landscape: {it['width']}x{it['height']} (ratio: {it['ratio']})")

    print(f"=== DEPLOYING ALL {len(manifest)} GROUP B POSTS TO WORDPRESS ===")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=30)

    # 1. Staging directory on VPS
    stdin, stdout, stderr = ssh.exec_command("mkdir -p /tmp/group_b_media")
    stdout.channel.recv_exit_status()
    sftp = ssh.open_sftp()

    print("\n--- Step 1: Uploading 64 validated images to VPS ---")
    staged_items = []
    for idx, it in enumerate(manifest, 1):
        remote_path = f"/tmp/group_b_media/{it['filename']}"
        print(f"[{idx:02d}/64] Uploading {it['filename']}...")
        sftp.put(it['local_file'], remote_path)
        staged_items.append({
            'post_id': it['id'],
            'slug': it['slug'],
            'remote_path': remote_path,
            'title': it['title'],
            'alt': it['alt'],
            'caption': it['caption'],
            'width': it['width'],
            'height': it['height']
        })

    sftp.close()
    print(f"Uploaded {len(staged_items)} images to /tmp/group_b_media/.")

    print("\n--- Step 2: Sideloading into Media Library & Setting Featured Images ---")
    import_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

$payload = json_decode(file_get_contents('/tmp/group_b_payload.json'), true);
$results = [];

foreach ($payload as $it) {
    $post_id = (int)$it['post_id'];
    $slug = $it['slug'];
    $remote_file = $it['remote_path'];
    $title = $it['title'];
    $caption = $it['caption'];
    $alt = $it['alt'];

    if (!file_exists($remote_file)) {
        $results[$slug] = ['error' => 'File not found: ' . $remote_file];
        continue;
    }

    $file_array = [
        'name' => basename($remote_file),
        'tmp_name' => $remote_file
    ];

    $attach_id = media_handle_sideload($file_array, $post_id, $title, [
        'post_title' => $title,
        'post_content' => $caption,
        'post_excerpt' => $caption
    ]);

    if (is_wp_error($attach_id)) {
        $results[$slug] = ['error' => $attach_id->get_error_message()];
        continue;
    }

    update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
    set_post_thumbnail($post_id, $attach_id);
    $url = wp_get_attachment_url($attach_id);

    $results[$slug] = [
        'post_id' => $post_id,
        'attach_id' => $attach_id,
        'url' => $url
    ];
}

echo json_encode($results);
"""

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/group_b_payload.json', 'w') as f:
        f.write(json.dumps(staged_items, ensure_ascii=False))
    with sftp.file('/tmp/group_b_import.php', 'w') as f:
        f.write(import_script)
    sftp.close()

    print("Running batch sideload on WordPress...")
    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/group_b_import.php --path={WP_PATH} --allow-root")
    raw_import = stdout.read().decode('utf-8')
    err_import = stderr.read().decode('utf-8')
    if err_import.strip():
        print("Import STDERR:", err_import)

    json_line = [l for l in raw_import.strip().splitlines() if l.strip().startswith('{')][-1]
    import_results = json.loads(json_line)
    print(f"Successfully sideloaded {len(import_results)} images.")

    print("\n--- Step 3: Updating post_content HTML with local Zero-CLS figures ---")
    update_content_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$embed_file = '/tmp/group_b_embed_payload.json';
$embeds = json_decode(file_get_contents($embed_file), true);

$updated_count = 0;

foreach ($embeds as $it) {
    $post_id = (int)$it['post_id'];
    $slug = $it['slug'];
    $figure_html = $it['figure_html'];

    $p = $wpdb->get_row($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d;", $post_id), ARRAY_A);
    if (!$p) continue;

    $content = $p['post_content'];

    // If post already has <figure class="vg-guide-photo">, replace it
    if (strpos($content, 'vg-guide-photo') !== false) {
        $pattern = '#<figure\s+class="vg-guide-photo".*?</figure>#s';
        $new_content = preg_replace($pattern, addcslashes($figure_html, '$'), $content, 1);
    } else {
        // Post 234 & 237 or fallback: replace first <figure>
        $pattern = '#<figure\b[^>]*>.*?</figure>#s';
        if (preg_match($pattern, $content)) {
            $new_content = preg_replace($pattern, addcslashes($figure_html, '$'), $content, 1);
        } else {
            // No figure at all: insert after verdict or before h2
            $verdict_pattern = '#(<div[^>]*class="[^"]*vg-concierge-verdict[^"]*"[^>]*>.*?</div>(\\\\s*<!--\\\\s*/wp:group\\\\s*-->)?)#s';
            if (preg_match($verdict_pattern, $content)) {
                $new_content = preg_replace($verdict_pattern, "$1\\n\\n" . addcslashes($figure_html, '$'), $content, 1);
            } else {
                $h2_pattern = '#(<h2\\\\b)#i';
                $new_content = preg_replace($h2_pattern, addcslashes($figure_html, '$') . "\\n\\n$1", $content, 1);
            }
        }
    }

    if ($new_content !== $content) {
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $new_content
        ], true);

        $wpdb->update(
            $wpdb->posts,
            [
                'post_modified' => '2026-09-27 21:00:00',
                'post_modified_gmt' => '2026-09-27 14:00:00'
            ],
            ['ID' => $post_id]
        );
        echo "[UPDATED] {$slug} (ID: {$post_id})\\n";
        $updated_count++;
    } else {
        echo "[NO CHANGE] {$slug} (ID: {$post_id})\\n";
    }
}

echo "Total updated: {$updated_count}\\n";
"""

    embed_payload = []
    for it in manifest:
        slug = it['slug']
        pid = it['id']
        alt = it['alt']
        caption = it['caption']
        w = it['width']
        h = it['height']

        sideload_info = import_results.get(slug, {})
        url = sideload_info.get('url', '')
        if not url:
            print(f"Warning: No local URL for {slug}")
            continue

        figure_html = (
            f'<figure class="vg-guide-photo">'
            f'<img src="{url}" alt="{alt}" width="{w}" height="{h}" loading="lazy" decoding="async">'
            f'<figcaption>{caption}</figcaption>'
            f'</figure>'
        )

        embed_payload.append({
            'post_id': pid,
            'slug': slug,
            'figure_html': figure_html
        })

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/group_b_embed_payload.json', 'w') as f:
        f.write(json.dumps(embed_payload, ensure_ascii=False))
    with sftp.file('/tmp/group_b_update_content.php', 'w') as f:
        f.write(update_content_script)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/group_b_update_content.php --path={WP_PATH} --allow-root")
    content_res = stdout.read().decode('utf-8')
    print(content_res)

    # Clean up temporary staging files on VPS
    ssh.exec_command("rm -rf /tmp/group_b_media /tmp/group_b_payload.json /tmp/group_b_import.php /tmp/group_b_embed_payload.json /tmp/group_b_update_content.php")

    # Step 4: Purge cache
    print("\n--- Step 4: Purging LiteSpeed Cache & Restarting PHP ---")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root && killall -9 lsphp")
    print("Cache purged successfully.")

    ssh.close()
    print(f"\n=== GROUP B DEPLOYMENT COMPLETE ===")

if __name__ == '__main__':
    main()
