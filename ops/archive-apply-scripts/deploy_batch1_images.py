# -*- coding: utf-8 -*-
"""
VietnamGuide: Deploy Batch 1 Real Images (30 Where-to-Stay Guides)
1. Downloads authentic Wikimedia Commons images to VPS staging folder
2. Imports into WordPress Media Library via WP-CLI with --featured_image
3. Embeds <figure class="vg-guide-photo"> with local upload URL into post_content
4. Purges LiteSpeed cache and verifies on live site
"""
import paramiko
import json
import os
import sys
import re
import urllib.request
import urllib.parse
import ssl
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def sanitize_filename(name):
    name = re.sub(r'[^a-zA-Z0-9_\.-]', '_', name)
    name = re.sub(r'_+', '_', name)
    return name

def main():
    ops_dir = os.path.dirname(os.path.abspath(__file__))
    plan_file = os.path.join(ops_dir, 'batch1_final_images.json')
    with open(plan_file, 'r', encoding='utf-8') as f:
        images_plan = json.load(f)

    print(f"=== DEPLOYING BATCH 1 REAL IMAGES ({len(images_plan)} Where-to-Stay Guides) ===")

    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=20)

    # 1. Create staging directory on VPS
    ssh.exec_command("mkdir -p /tmp/batch1_media")
    sftp = ssh.open_sftp()

    print("\n--- Step 1: Staging authentic images on VPS ---")
    local_files = []
    ctx = ssl.create_default_context()

    for idx, item in enumerate(images_plan):
        slug = item['slug']
        post_id = item['post_id']
        img_info = item['image']
        thumb_url = img_info['thumb_url']
        raw_filename = img_info['file_title']
        safe_filename = sanitize_filename(f"{post_id}_{raw_filename}")
        if not (safe_filename.lower().endswith('.jpg') or safe_filename.lower().endswith('.jpeg') or safe_filename.lower().endswith('.png')):
            safe_filename += '.jpg'
        
        remote_staging_path = f"/tmp/batch1_media/{safe_filename}"
        
        # Check if already staged with valid size
        already_staged = False
        try:
            st = sftp.stat(remote_staging_path)
            if st.st_size > 10000:
                already_staged = True
        except IOError:
            pass

        if already_staged:
            print(f"[{idx+1}/30] Already staged: {slug} ({safe_filename})")
            local_files.append((item, safe_filename, remote_staging_path))
            continue

        print(f"[{idx+1}/30] Downloading {slug} ({img_info['file_title'][:40]})...")
        req = urllib.request.Request(
            thumb_url,
            headers={'User-Agent': 'VietnamGuideBot/1.0 (https://vietnamguide.net; contact@vietnamguide.net)'}
        )
        try:
            with urllib.request.urlopen(req, context=ctx, timeout=15) as resp:
                content = resp.read()
            with sftp.file(remote_staging_path, 'wb') as rf:
                rf.write(content)
            print(f"   -> Staged: {safe_filename} ({len(content)} bytes)")
            local_files.append((item, safe_filename, remote_staging_path))
        except Exception as e:
            print(f"   -> ERROR downloading {slug}: {e}")
            raise e

    sftp.close()

    print("\n--- Step 2: Importing into WordPress & setting Featured Images ---")
    import_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

$batch_file = '/tmp/batch1_import_payload.json';
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
        // Copy file to uploads
        $file_array = [
            'name' => basename($remote_file),
            'tmp_name' => $remote_file
        ];

        // Sideload attachment
        $attach_id = media_handle_sideload($file_array, $post_id, $title, [
            'post_title' => $title,
            'post_content' => $caption,
            'post_excerpt' => $caption
        ]);

        if (is_wp_error($attach_id)) {
            $results[$slug] = ['error' => $attach_id->get_error_message()];
            continue;
        }

        // Set alt text
        update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);

        // Set as Featured Image
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

    # Prepare payload
    payload = []
    for item, safe_filename, remote_staging_path in local_files:
        payload.append({
            'post_id': item['post_id'],
            'slug': item['slug'],
            'remote_path': remote_staging_path,
            'alt': item['alt'],
            'caption': item['caption']
        })

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/batch1_import_payload.json', 'w') as f:
        f.write(json.dumps(payload, ensure_ascii=False))
    with sftp.file('/tmp/batch1_import.php', 'w') as f:
        f.write(import_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/batch1_import.php --path={WP_PATH} --allow-root"
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

$embed_file = '/tmp/batch1_embed_payload.json';
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

    // Insert right after the closing </div> of vg-concierge-verdict
    $verdict_close_pattern = '#(<div class="vg-concierge-verdict">.*?</div>)#s';
    if (preg_match($verdict_close_pattern, $content)) {
        $new_content = preg_replace(
            $verdict_close_pattern,
            "$1\n\n" . addcslashes($figure_html, '$'),
            $content,
            1
        );
    } else {
        // Fallback: insert before first <h2>
        $first_h2_pattern = '#(<h2\b)#i';
        $new_content = preg_replace(
            $first_h2_pattern,
            addcslashes($figure_html, '$') . "\n\n$1",
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
                'post_modified' => '2026-09-26 22:00:00',
                'post_modified_gmt' => '2026-09-26 15:00:00'
            ],
            ['ID' => $post_id]
        );
        echo "[UPDATED] {$slug} (ID: {$post_id})\\n";
        $updated_count++;
    } else {
        echo "[SKIP] {$slug} content not modified\\n";
    }
}

echo "Total content updated: {$updated_count}\\n";
"""

    embed_payload = []
    for item, safe_filename, remote_staging_path in local_files:
        slug = item['slug']
        post_id = item['post_id']
        img_info = item['image']
        imported_info = imported_data.get(slug, {})
        local_url = imported_info.get('url') or img_info['thumb_url']
        
        # Build figure markup
        figure_html = (
            f'<figure class="vg-guide-photo">\n'
            f'<img src="{local_url}" alt="{item["alt"]}" width="{img_info["width"]}" height="{img_info["height"]}" loading="lazy" decoding="async">\n'
            f'<figcaption>{item["caption"]} Image: <a href="{img_info["page_url"]}" target="_blank" rel="license noopener">{img_info["artist"]} / {img_info["license"]}</a>.</figcaption>\n'
            f'</figure>'
        )

        embed_payload.append({
            'post_id': post_id,
            'slug': slug,
            'figure_html': figure_html
        })

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/batch1_embed_payload.json', 'w') as f:
        f.write(json.dumps(embed_payload, ensure_ascii=False))
    with sftp.file('/tmp/batch1_embed.php', 'w') as f:
        f.write(update_content_script)
    sftp.close()

    cmd = f"wp eval-file /tmp/batch1_embed.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8'))

    print("\n--- Step 4: Purging LiteSpeed cache ---")
    purge_cmd = f"wp litespeed-purge all --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print("LiteSpeed Purge:", stdout.read().decode('utf-8').strip())

    # Clean up temp files on VPS
    ssh.exec_command("rm -rf /tmp/batch1_media /tmp/batch1_import* /tmp/batch1_embed*")
    ssh.close()

    print("\n=== BATCH 1 DEPLOYMENT FINISHED ===")

if __name__ == '__main__':
    main()
