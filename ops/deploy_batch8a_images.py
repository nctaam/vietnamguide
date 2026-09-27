# -*- coding: utf-8 -*-
"""
VietnamGuide: Deploy Batch 8A Real Images (28 Practical Travel & Transit Guides)
1. Downloads 28 curated authentic images from Wikimedia Commons to VPS /tmp/batch8a_media/
2. Sideloads into WordPress Media Library (/wp-content/uploads/2026/09/)
3. Sets _thumbnail_id (Featured Image) for OpenGraph / social previews
4. Inserts <figure class="vg-guide-photo"> with explicit width/height and attribution
5. Purges LiteSpeed cache & restarts lsphp
"""
import paramiko
import urllib.request
import urllib.parse
import json
import ssl
import sys
import os
import re

sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    plan_file = os.path.join(ops_dir, 'batch8a_final_images.json')
    if not os.path.exists(plan_file):
        print(f"Error: {plan_file} not found.")
        sys.exit(1)

    with open(plan_file, 'r', encoding='utf-8') as f:
        images_plan = json.load(f)

    print(f"=== DEPLOYING BATCH 8A REAL IMAGES ({len(images_plan)} Practical Travel & Transit Guides) ===")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=25)

    # 1. Create staging directory on VPS
    ssh.exec_command("mkdir -p /tmp/batch8a_media")
    sftp = ssh.open_sftp()

    print("\n--- Step 1: Staging authentic images on VPS ---")
    local_files = []
    ctx = ssl.create_default_context()

    for idx, item in enumerate(images_plan):
        slug = item['slug']
        post_id = item['id']
        meta = item['meta']
        thumb_url = meta['thumburl']
        source_title = item['source_file'].replace('File:', '')

        ext = '.jpg'
        if '.' in source_title:
            ext = os.path.splitext(source_title)[1]
            if ext.lower() not in ['.jpg', '.jpeg', '.png', '.webp']:
                ext = '.jpg'

        clean_slug = re.sub(r'[^a-zA-Z0-9_\-]', '', slug)
        safe_filename = f"{post_id}_{clean_slug}{ext}"
        remote_staging_path = f"/tmp/batch8a_media/{safe_filename}"

        # Check if already staged on VPS
        try:
            st = sftp.stat(remote_staging_path)
            if st.st_size > 1000:
                print(f"[{idx+1:02d}/{len(images_plan)}] Already staged: {safe_filename} ({st.st_size} bytes)")
                local_files.append((item, safe_filename, remote_staging_path))
                continue
        except IOError:
            pass

        print(f"[{idx+1:02d}/{len(images_plan)}] Downloading {source_title[:45]}...")
        req = urllib.request.Request(
            thumb_url,
            headers={'User-Agent': 'VietnamGuideBot/1.0 (travel@vietnamguide.net)'}
        )

        try:
            with urllib.request.urlopen(req, context=ctx, timeout=25) as resp:
                data = resp.read()
                with sftp.file(remote_staging_path, 'wb') as rf:
                    rf.write(data)
                local_files.append((item, safe_filename, remote_staging_path))
                print(f"       -> Uploaded to VPS: {remote_staging_path} ({len(data)} bytes)")
        except Exception as e:
            print(f"       ERROR downloading {thumb_url}: {e}")

    sftp.close()

    print(f"\nSuccessfully staged {len(local_files)} images on VPS.")

    print("\n--- Step 2: Importing into WordPress & setting Featured Images ---")
    import_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

$batch_file = '/tmp/batch8a_import_payload.json';
$items = json_decode(file_get_contents($batch_file), true);

$results = [];

foreach ($items as $it) {
    $post_id = (int)$it['post_id'];
    $slug = $it['slug'];
    $remote_file = $it['remote_path'];
    $title = $it['alt'];
    $caption = $it['caption'];
    $alt = $it['alt'];

    if (!file_exists($remote_file)) {
        $results[$slug] = ['error' => 'File not found: ' . $remote_file];
        continue;
    }

    // Check if thumbnail already attached
    $existing_thumb = get_post_thumbnail_id($post_id);
    if ($existing_thumb) {
        $attach_id = $existing_thumb;
        $url = wp_get_attachment_url($attach_id);
    } else {
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
    }

    $results[$slug] = [
        'post_id' => $post_id,
        'attach_id' => $attach_id,
        'url' => $url
    ];
}

echo json_encode($results);
"""

    payload = []
    for item, safe_filename, remote_staging_path in local_files:
        payload.append({
            'post_id': item['id'],
            'slug': item['slug'],
            'remote_path': remote_staging_path,
            'alt': item['alt'],
            'caption': item['caption']
        })

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/batch8a_import_payload.json', 'w') as f:
        f.write(json.dumps(payload, ensure_ascii=False))
    with sftp.file('/tmp/batch8a_import.php', 'w') as f:
        f.write(import_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/batch8a_import.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    raw_import_out = stdout.read().decode('utf-8')
    err_out = stderr.read().decode('utf-8')

    if err_out.strip():
        print("Import STDERR:", err_out)

    json_line = [l for l in raw_import_out.strip().splitlines() if l.strip().startswith('{')][-1]
    imported_data = json.loads(json_line)

    print(f"Successfully processed {len(imported_data)} images in WordPress Media Library.")

    print("\n--- Step 3: Embedding <figure class=\"vg-guide-photo\"> into post_content ---")
    update_content_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$embed_file = '/tmp/batch8a_embed_payload.json';
$embeds = json_decode(file_get_contents($embed_file), true);

$updated_count = 0;

foreach ($embeds as $it) {
    $post_id = (int)$it['post_id'];
    $slug = $it['slug'];
    $figure_html = $it['figure_html'];

    $p = $wpdb->get_row($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d;", $post_id), ARRAY_A);
    if (!$p) continue;

    $content = $p['post_content'];

    // Don't insert duplicate if already present
    if (strpos($content, 'vg-guide-photo') !== false) {
        echo "[EXISTS] {$slug} already has vg-guide-photo\\n";
        continue;
    }

    // Insert right after the closing </div> or wp:group of vg-concierge-verdict
    $verdict_close_pattern = '#(<div[^>]*class="[^"]*vg-concierge-verdict[^"]*"[^>]*>.*?</div>(\\\\s*<!--\\\\s*/wp:group\\\\s*-->)?)#s';
    if (preg_match($verdict_close_pattern, $content)) {
        $new_content = preg_replace(
            $verdict_close_pattern,
            "$1\\n\\n" . addcslashes($figure_html, '$'),
            $content,
            1
        );
    } else {
        // Fallback: insert before first <h2>
        $first_h2_pattern = '#(<h2\\\\b)#i';
        $new_content = preg_replace(
            $first_h2_pattern,
            addcslashes($figure_html, '$') . "\\n\\n$1",
            $content,
            1
        );
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
    for item in images_plan:
        slug = item['slug']
        pid = item['id']
        alt = item['alt']
        caption = item['caption']
        tw = item['meta']['thumbwidth']
        th = item['meta']['thumbheight']

        imp_info = imported_data.get(slug, {})
        url = imp_info.get('url', '')
        if not url:
            print(f"Warning: No local URL for {slug}")
            continue

        figure_html = (
            f'<figure class="vg-guide-photo">'
            f'<img src="{url}" alt="{alt}" width="{tw}" height="{th}" loading="lazy" decoding="async">'
            f'<figcaption>{caption}</figcaption>'
            f'</figure>'
        )

        embed_payload.append({
            'post_id': pid,
            'slug': slug,
            'figure_html': figure_html
        })

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/batch8a_embed_payload.json', 'w') as f:
        f.write(json.dumps(embed_payload, ensure_ascii=False))
    with sftp.file('/tmp/batch8a_update_content.php', 'w') as f:
        f.write(update_content_script)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/batch8a_update_content.php --path={WP_PATH} --allow-root")
    content_res = stdout.read().decode('utf-8')
    print(content_res)

    # Clean up temporary files on VPS
    ssh.exec_command("rm -rf /tmp/batch8a_media /tmp/batch8a_import_payload.json /tmp/batch8a_import.php /tmp/batch8a_embed_payload.json /tmp/batch8a_update_content.php")

    # Step 4: Purge cache
    print("\n--- Step 4: Purging LiteSpeed Cache & Restarting PHP ---")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root")
    ssh.exec_command(f"wp cache flush --path={WP_PATH} --allow-root")
    ssh.exec_command("killall -9 lsphp")
    print("Cache purged successfully.")

    ssh.close()
    print(f"\n=== BATCH 8A DEPLOYMENT COMPLETE ===")

if __name__ == '__main__':
    main()
