<?php
/**
 * Plugin Name: VietnamGuide Core
 * Description: Site-specific functionality for VietnamGuide.net.
 * Version: 0.1.6
 * Author: VietnamGuide.net
 */

if (! defined('ABSPATH')) {
    exit;
}

const VG_EEAT_META_KEYS = [
    'primary_decision'       => 'vg_eeat_primary_decision',
    'reviewed_guide'         => 'vg_eeat_reviewed_guide',
    'written_by'             => 'vg_eeat_written_by',
    'reviewed_by'            => 'vg_eeat_reviewed_by',
    'last_meaningful_update' => 'vg_eeat_last_meaningful_update',
    'update_summary'         => 'vg_eeat_update_summary',
    'sources_checked'        => 'vg_eeat_sources_checked',
    'field_note'             => 'vg_eeat_field_note',
    'affiliate_status'       => 'vg_eeat_affiliate_status',
    'evidence_moat'          => 'vg_eeat_evidence_moat',
    'related_routes'         => 'vg_eeat_related_routes',
    'hero_image_credit'      => 'vg_eeat_hero_image_credit',
];

const VG_ADMIN_FIRST_META_KEYS = [
    'content_owner'      => 'vg_content_owner',
    'automation_lock'    => 'vg_automation_lock',
    'last_manual_review' => 'vg_last_manual_review',
    'workflow_notes'     => 'vg_admin_first_notes',
    'automation_hash'    => '_vg_last_automation_content_hash',
    'meta_hash_prefix'   => '_vg_last_automation_meta_hash_',
];

function vg_eeat_meta_key(string $name): string
{
    return VG_EEAT_META_KEYS[$name] ?? '';
}

function vg_admin_first_meta_key(string $name): string
{
    return VG_ADMIN_FIRST_META_KEYS[$name] ?? '';
}

function vg_admin_first_value_is_truthy(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (float) $value > 0;
    }

    if (is_string($value)) {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    return false;
}

function vg_admin_first_env_is_truthy(string $name): bool
{
    $value = getenv($name);

    return vg_admin_first_value_is_truthy($value);
}

function vg_admin_first_automation_overwrite_allowed(): bool
{
    if (defined('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE') && vg_admin_first_value_is_truthy(constant('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE'))) {
        return true;
    }

    return vg_admin_first_env_is_truthy('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE')
        || vg_admin_first_env_is_truthy('VG_FORCE_WP_ADMIN_OVERWRITE');
}

function vg_admin_first_guard_applies_to_request(): bool
{
    return defined('WP_CLI')
        && WP_CLI
        && ! vg_admin_first_automation_overwrite_allowed();
}

function vg_admin_first_is_wp_cli_context(): bool
{
    return defined('WP_CLI') && WP_CLI;
}

function vg_admin_first_post_type_is_guarded(string $post_type): bool
{
    return in_array($post_type, ['page', 'post'], true);
}

function vg_admin_first_post_is_admin_owned(int $post_id): bool
{
    if ($post_id <= 0 || ! vg_admin_first_post_type_is_guarded((string) get_post_type($post_id))) {
        return false;
    }

    $owner = trim((string) get_post_meta($post_id, 'vg_content_owner', true));
    $lock = trim((string) get_post_meta($post_id, 'vg_automation_lock', true));

    return $owner === 'wp_admin' || $lock === 'locked';
}

function vg_admin_first_automation_content_hash_key(): string
{
    return '_vg_last_automation_content_hash';
}

function vg_admin_first_automation_meta_hash_prefix(): string
{
    return '_vg_last_automation_meta_hash_';
}

function vg_admin_first_value_hash(mixed $value): string
{
    $json = wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if (! is_string($json)) {
        $json = serialize($value);
    }

    return hash('sha256', $json);
}

function vg_admin_first_post_hash_payload(WP_Post $post): array
{
    return [
        'post_title'   => (string) $post->post_title,
        'post_name'    => (string) $post->post_name,
        'post_content' => (string) $post->post_content,
        'post_excerpt' => (string) $post->post_excerpt,
        'post_status'  => (string) $post->post_status,
        'post_parent'  => (int) $post->post_parent,
        'menu_order'   => (int) $post->menu_order,
    ];
}

function vg_admin_first_post_content_hash(WP_Post $post): string
{
    return vg_admin_first_value_hash(vg_admin_first_post_hash_payload($post));
}

function vg_admin_first_post_content_has_manual_drift(WP_Post $post): bool
{
    $stored_hash = trim((string) get_post_meta((int) $post->ID, vg_admin_first_automation_content_hash_key(), true));

    if ($stored_hash === '') {
        return false;
    }

    return ! hash_equals($stored_hash, vg_admin_first_post_content_hash($post));
}

function vg_admin_first_meta_hash_key(string $meta_key): string
{
    return vg_admin_first_automation_meta_hash_prefix() . md5($meta_key);
}

function vg_admin_first_meta_has_manual_drift(int $post_id, string $meta_key, mixed $incoming_value): bool
{
    $hash_meta_key = vg_admin_first_meta_hash_key($meta_key);
    $stored_hash = trim((string) get_post_meta($post_id, $hash_meta_key, true));

    if ($stored_hash === '') {
        return false;
    }

    $current_value = get_post_meta($post_id, $meta_key, true);
    $current_hash = vg_admin_first_value_hash($current_value);

    if (hash_equals($stored_hash, $current_hash)) {
        return false;
    }

    return ! hash_equals($current_hash, vg_admin_first_value_hash($incoming_value));
}

function vg_admin_first_guard_abort(string $message): void
{
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
        WP_CLI::error($message);
    }

    if (function_exists('wp_die')) {
        wp_die(esc_html($message), esc_html__('VietnamGuide Admin-first Guard', 'vietnamguide-core'), ['response' => 403]);
    }

    throw new RuntimeException($message);
}

function vg_admin_first_changed_protected_post_fields(array $data, WP_Post $existing): array
{
    $protected_fields = [
        'post_title',
        'post_name',
        'post_content',
        'post_excerpt',
        'post_status',
        'post_parent',
        'menu_order',
    ];
    $changed_fields = [];

    foreach ($protected_fields as $field) {
        if (! array_key_exists($field, $data)) {
            continue;
        }

        $current_value = (string) ($existing->{$field} ?? '');
        $incoming_value = (string) $data[$field];

        if ($incoming_value !== $current_value) {
            $changed_fields[] = $field;
        }
    }

    return $changed_fields;
}

function vg_admin_first_protect_post_writes(array $data, array $postarr, array $unsanitized_postarr, bool $update): array
{
    if (! $update || ! vg_admin_first_guard_applies_to_request()) {
        return $data;
    }

    $post_id = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;

    if ($post_id <= 0 || ! vg_admin_first_post_is_admin_owned($post_id)) {
        $existing = get_post($post_id);

        if ($existing instanceof WP_Post && vg_admin_first_post_content_has_manual_drift($existing) && vg_admin_first_changed_protected_post_fields($data, $existing) !== []) {
            vg_admin_first_guard_abort(
                'VietnamGuide Admin-first Guard detected manual content drift for post '
                . $post_id
                . ' since the last automation baseline. Review this item in WordPress Admin, mark it wp_admin/locked if it is manually owned, or rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after creating a backup.'
            );
        }

        return $data;
    }

    $existing = get_post($post_id);

    if (! $existing instanceof WP_Post) {
        return $data;
    }

    $changed_fields = vg_admin_first_changed_protected_post_fields($data, $existing);

    if ($changed_fields !== []) {
        vg_admin_first_guard_abort(
            'VietnamGuide Admin-first Guard blocked automated WP-CLI overwrite for post '
            . $post_id
            . ' on fields: '
            . implode(', ', $changed_fields)
            . '. Edit this content in WordPress Admin, or rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after creating a backup.'
        );
    }

    return $data;
}
add_filter('wp_insert_post_data', 'vg_admin_first_protect_post_writes', 1, 4);

function vg_admin_first_record_post_automation_hash(int $post_id, WP_Post $post, bool $update, ?WP_Post $post_before): void
{
    if (! vg_admin_first_is_wp_cli_context() || ! vg_admin_first_post_type_is_guarded((string) $post->post_type)) {
        return;
    }

    update_post_meta($post_id, vg_admin_first_automation_content_hash_key(), vg_admin_first_post_content_hash($post));
}
add_action('wp_after_insert_post', 'vg_admin_first_record_post_automation_hash', 20, 4);

function vg_admin_first_meta_key_is_workflow_control(string $meta_key): bool
{
    return in_array($meta_key, array_values(VG_ADMIN_FIRST_META_KEYS), true)
        || str_starts_with($meta_key, vg_admin_first_automation_meta_hash_prefix());
}

function vg_admin_first_meta_key_is_protected(string $meta_key): bool
{
    if (vg_admin_first_meta_key_is_workflow_control($meta_key)) {
        return false;
    }

    if (str_starts_with($meta_key, 'rank_math_') || str_starts_with($meta_key, '_rank_math_')) {
        return true;
    }

    if (str_starts_with($meta_key, 'vg_eeat_')) {
        return true;
    }

    return in_array(
        $meta_key,
        [
            '_generate-disable-headline',
            '_thumbnail_id',
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_focuskw',
            '_yoast_wpseo_canonical',
            '_yoast_wpseo_meta-robots-noindex',
        ],
        true
    );
}

function vg_admin_first_protect_post_meta(mixed $check, int $object_id, string $meta_key, mixed $meta_value, mixed $prev_value): mixed
{
    if (! vg_admin_first_guard_applies_to_request()) {
        return $check;
    }

    if (! vg_admin_first_meta_key_is_protected($meta_key)) {
        return $check;
    }

    if (! vg_admin_first_post_is_admin_owned($object_id)) {
        if (vg_admin_first_meta_has_manual_drift($object_id, $meta_key, $meta_value)) {
            vg_admin_first_guard_abort(
                'VietnamGuide Admin-first Guard detected manual metadata drift for post '
                . $object_id
                . ' on meta key: '
                . $meta_key
                . ' since the last automation baseline. Review this item in WordPress Admin, mark it wp_admin/locked if it is manually owned, or rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after creating a backup.'
            );
        }

        return $check;
    }

    vg_admin_first_guard_abort(
        'VietnamGuide Admin-first Guard blocked automated metadata overwrite for post '
        . $object_id
        . ' on meta key: '
        . $meta_key
        . '. Edit this field in WordPress Admin, or rerun with VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1 after creating a backup.'
    );

    return $check;
}
add_filter('update_post_metadata', 'vg_admin_first_protect_post_meta', 1, 5);

function vg_admin_first_record_protected_meta_automation_hash(int $meta_id, int $post_id, string $meta_key, mixed $meta_value): void
{
    if (! vg_admin_first_is_wp_cli_context() || vg_admin_first_meta_key_is_workflow_control($meta_key) || ! vg_admin_first_meta_key_is_protected($meta_key)) {
        return;
    }

    update_post_meta($post_id, vg_admin_first_meta_hash_key($meta_key), vg_admin_first_value_hash(get_post_meta($post_id, $meta_key, true)));
}
add_action('updated_post_meta', 'vg_admin_first_record_protected_meta_automation_hash', 20, 4);
add_action('added_post_meta', 'vg_admin_first_record_protected_meta_automation_hash', 20, 4);

function vg_eeat_get_field(int $post_id, string $name, string $default = ''): string
{
    $key = vg_eeat_meta_key($name);

    if ($key === '') {
        return $default;
    }

    $value = get_post_meta($post_id, $key, true);

    if (is_scalar($value)) {
        $value = trim((string) $value);
        return $value === '' ? $default : $value;
    }

    return $default;
}

function vg_eeat_lines(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $clean_lines = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $clean_lines[] = $line;
    }

    return $clean_lines;
}

function vg_eeat_render_list(array $items, string $class_name): string
{
    if ($items === []) {
        return '';
    }

    $output = '<ul class="' . esc_attr($class_name) . '">';

    foreach ($items as $item) {
        $output .= '<li>' . esc_html($item) . '</li>';
    }

    $output .= '</ul>';

    return $output;
}

function vg_eeat_related_route_items(string $value): array
{
    $items = [];

    foreach (vg_eeat_lines($value) as $line) {
        $parts = array_map('trim', explode('|', $line, 3));

        if (count($parts) < 2) {
            continue;
        }

        $label = $parts[0];
        $url = $parts[1];
        $note = $parts[2] ?? '';

        if ($label === '' || $url === '') {
            continue;
        }

        $items[] = [
            'label' => $label,
            'url'   => $url,
            'note'  => $note,
        ];
    }

    return $items;
}

function vg_shortcode_editorial_proof_panel(array $atts = []): string
{
    $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'vg_editorial_proof');
    $post_id = (int) $atts['id'];

    if ($post_id <= 0) {
        return '';
    }

    $written_by = vg_eeat_get_field($post_id, 'written_by', 'VietnamGuide editorial team');
    $reviewed_by = vg_eeat_get_field($post_id, 'reviewed_by', 'VietnamGuide editorial review');
    $updated = vg_eeat_get_field($post_id, 'last_meaningful_update', get_the_modified_date('F j, Y', $post_id));
    $summary = vg_eeat_get_field($post_id, 'update_summary', 'Initial editorial review completed.');
    $affiliate_status = vg_eeat_get_field($post_id, 'affiliate_status', 'none');

    $affiliate_label = [
        'none' => __('No affiliate links', 'vietnamguide-core'),
        'editorial' => __('Editorial affiliate links may appear', 'vietnamguide-core'),
        'sponsored' => __('Sponsored content disclosure required', 'vietnamguide-core'),
    ][$affiliate_status] ?? __('Affiliate status reviewed', 'vietnamguide-core');

    return sprintf(
        '<aside class="vg-proof-panel" aria-label="%1$s"><p class="vg-kicker">%2$s</p><dl><div><dt>%3$s</dt><dd>%4$s</dd></div><div><dt>%5$s</dt><dd>%6$s</dd></div><div><dt>%7$s</dt><dd>%8$s</dd></div><div><dt>%9$s</dt><dd>%10$s</dd></div><div><dt>%11$s</dt><dd>%12$s</dd></div></dl><p><a href="%13$s">%14$s</a></p></aside>',
        esc_attr__('Editorial proof', 'vietnamguide-core'),
        esc_html__('Editorial proof', 'vietnamguide-core'),
        esc_html__('Written by', 'vietnamguide-core'),
        esc_html($written_by),
        esc_html__('Reviewed by', 'vietnamguide-core'),
        esc_html($reviewed_by),
        esc_html__('Last meaningful update', 'vietnamguide-core'),
        esc_html($updated),
        esc_html__('What changed', 'vietnamguide-core'),
        esc_html($summary),
        esc_html__('Affiliate status', 'vietnamguide-core'),
        esc_html($affiliate_label),
        esc_url(home_url('/source-update-policy/')),
        esc_html__('How VietnamGuide reviews and updates guides', 'vietnamguide-core')
    );
}
add_shortcode('vg_editorial_proof', 'vg_shortcode_editorial_proof_panel');

function vg_shortcode_source_trail(array $atts = []): string
{
    $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'vg_source_trail');
    $post_id = (int) $atts['id'];

    if ($post_id <= 0) {
        return '';
    }

    $sources = vg_eeat_lines(vg_eeat_get_field($post_id, 'sources_checked'));
    $field_note = vg_eeat_get_field($post_id, 'field_note');

    if ($sources === [] && $field_note === '') {
        return '';
    }

    $output = '<aside class="vg-source-trail" aria-label="' . esc_attr__('Source trail', 'vietnamguide-core') . '">';
    $output .= '<p class="vg-kicker">' . esc_html__('Source trail', 'vietnamguide-core') . '</p>';

    if ($field_note !== '') {
        $output .= '<p class="vg-field-note">' . esc_html($field_note) . '</p>';
    }

    $output .= vg_eeat_render_list($sources, 'vg-source-list');
    $output .= '</aside>';

    return $output;
}
add_shortcode('vg_source_trail', 'vg_shortcode_source_trail');

function vg_shortcode_update_log(array $atts = []): string
{
    $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'vg_update_log');
    $post_id = (int) $atts['id'];

    if ($post_id <= 0) {
        return '';
    }

    $updated = vg_eeat_get_field($post_id, 'last_meaningful_update', get_the_modified_date('F j, Y', $post_id));
    $summary = vg_eeat_get_field($post_id, 'update_summary', 'Initial editorial review completed.');
    $reviewed_by = vg_eeat_get_field($post_id, 'reviewed_by', 'VietnamGuide editorial review');

    return sprintf(
        '<section class="vg-update-log" aria-label="%1$s"><p class="vg-kicker">%2$s</p><div class="vg-update-log-row"><time>%3$s</time><p>%4$s</p><p>%5$s</p></div></section>',
        esc_attr__('Update log', 'vietnamguide-core'),
        esc_html__('Update log', 'vietnamguide-core'),
        esc_html($updated),
        esc_html($summary),
        esc_html(sprintf(__('Reviewed by %s', 'vietnamguide-core'), $reviewed_by))
    );
}
add_shortcode('vg_update_log', 'vg_shortcode_update_log');

function vg_shortcode_related_routes(array $atts = []): string
{
    $atts = shortcode_atts(
        [
            'id'    => get_the_ID(),
            'title' => __('Where this guide fits next', 'vietnamguide-core'),
            'intro' => __('Use these related decisions to move from reading to booking with fewer blind spots.', 'vietnamguide-core'),
        ],
        $atts,
        'vg_related_routes'
    );
    $post_id = (int) $atts['id'];

    if ($post_id <= 0) {
        return '';
    }

    $items = vg_eeat_related_route_items(vg_eeat_get_field($post_id, 'related_routes'));

    if ($items === []) {
        return '';
    }

    $heading_id = 'vg-related-routes-title-' . $post_id;
    $primary_decision = vg_eeat_get_field($post_id, 'primary_decision');
    $output = '<section class="vg-related-routes" aria-labelledby="' . esc_attr($heading_id) . '">';
    $output .= '<div class="vg-related-routes-head">';
    $output .= '<div><p class="vg-kicker">' . esc_html__('Related decisions', 'vietnamguide-core') . '</p>';
    $output .= '<h2 id="' . esc_attr($heading_id) . '">' . esc_html((string) $atts['title']) . '</h2></div>';
    $output .= '<div><p>' . esc_html((string) $atts['intro']) . '</p>';

    if ($primary_decision !== '') {
        $output .= '<p class="vg-related-routes-context"><strong>' . esc_html__('Main decision:', 'vietnamguide-core') . '</strong> ' . esc_html($primary_decision) . '</p>';
    }

    $output .= '</div></div>';
    $output .= '<ol class="vg-related-route-list">';

    foreach ($items as $index => $item) {
        $output .= '<li>';
        $output .= '<span class="vg-related-route-step">' . esc_html(sprintf('%02d', $index + 1)) . '</span>';
        $output .= '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';

        if ($item['note'] !== '') {
            $output .= '<span class="vg-related-route-note">' . esc_html($item['note']) . '</span>';
        }

        $output .= '</li>';
    }

    $output .= '</ol></section>';

    return $output;
}
add_shortcode('vg_related_routes', 'vg_shortcode_related_routes');

function vg_register_eeat_acf_fields(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_vg_eeat_guide_trust',
        'title' => 'VietnamGuide EEAT Trust Fields',
        'fields' => [
            [
                'key' => 'field_vg_eeat_primary_decision',
                'label' => 'Primary traveler decision',
                'name' => 'vg_eeat_primary_decision',
                'type' => 'text',
                'instructions' => 'One sentence describing the decision this page helps the traveler make.',
            ],
            [
                'key' => 'field_vg_eeat_reviewed_guide',
                'label' => 'Reviewed guide',
                'name' => 'vg_eeat_reviewed_guide',
                'type' => 'true_false',
                'ui' => 1,
            ],
            [
                'key' => 'field_vg_eeat_written_by',
                'label' => 'Written by',
                'name' => 'vg_eeat_written_by',
                'type' => 'text',
                'default_value' => 'VietnamGuide editorial team',
            ],
            [
                'key' => 'field_vg_eeat_reviewed_by',
                'label' => 'Reviewed by',
                'name' => 'vg_eeat_reviewed_by',
                'type' => 'text',
                'default_value' => 'VietnamGuide editorial review',
            ],
            [
                'key' => 'field_vg_eeat_last_meaningful_update',
                'label' => 'Last meaningful update',
                'name' => 'vg_eeat_last_meaningful_update',
                'type' => 'text',
                'instructions' => 'Use a human-readable date, for example July 13, 2026.',
            ],
            [
                'key' => 'field_vg_eeat_update_summary',
                'label' => 'What changed',
                'name' => 'vg_eeat_update_summary',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_vg_eeat_sources_checked',
                'label' => 'Sources checked',
                'name' => 'vg_eeat_sources_checked',
                'type' => 'textarea',
                'rows' => 5,
                'instructions' => 'One source per line. Include official sources and checked dates where relevant.',
            ],
            [
                'key' => 'field_vg_eeat_field_note',
                'label' => 'Field note',
                'name' => 'vg_eeat_field_note',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_vg_eeat_affiliate_status',
                'label' => 'Affiliate status',
                'name' => 'vg_eeat_affiliate_status',
                'type' => 'select',
                'choices' => [
                    'none' => 'No affiliate links',
                    'editorial' => 'Editorial affiliate links',
                    'sponsored' => 'Sponsored content',
                ],
                'default_value' => 'none',
                'ui' => 1,
            ],
            [
                'key' => 'field_vg_eeat_evidence_moat',
                'label' => 'Evidence moat',
                'name' => 'vg_eeat_evidence_moat',
                'type' => 'textarea',
                'rows' => 4,
                'instructions' => 'One line per moat item: field note, local review, official source interpretation, original comparison, update log.',
            ],
            [
                'key' => 'field_vg_eeat_related_routes',
                'label' => 'Related planning decisions',
                'name' => 'vg_eeat_related_routes',
                'type' => 'textarea',
                'rows' => 4,
                'instructions' => 'One related guide per line using: Label | /path/ | Why the reader should go there next.',
            ],
            [
                'key' => 'field_vg_eeat_hero_image_credit',
                'label' => 'Hero image credit',
                'name' => 'vg_eeat_hero_image_credit',
                'type' => 'text',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'active' => true,
    ]);
}
add_action('acf/init', 'vg_register_eeat_acf_fields');

function vg_register_admin_first_acf_fields(): void
{
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_vg_admin_first_workflow',
        'title' => 'VietnamGuide Admin-First Workflow',
        'fields' => [
            [
                'key' => 'field_vg_content_owner',
                'label' => 'Content owner',
                'name' => 'vg_content_owner',
                'type' => 'select',
                'instructions' => 'Use wp_admin when this page or post should be edited in WordPress Admin and protected from automated content republishing.',
                'choices' => [
                    'automation' => 'Automation/bootstrap script',
                    'wp_admin' => 'WordPress Admin',
                ],
                'default_value' => 'automation',
                'ui' => 1,
            ],
            [
                'key' => 'field_vg_automation_lock',
                'label' => 'Automation lock',
                'name' => 'vg_automation_lock',
                'type' => 'select',
                'instructions' => 'Use locked after manual editorial optimization begins. WP-CLI automation must use an explicit override to change protected content or SEO fields.',
                'choices' => [
                    'open' => 'Open to automation',
                    'locked' => 'Locked for WordPress Admin editing',
                ],
                'default_value' => 'open',
                'ui' => 1,
            ],
            [
                'key' => 'field_vg_last_manual_review',
                'label' => 'Last manual editorial review',
                'name' => 'vg_last_manual_review',
                'type' => 'text',
                'instructions' => 'Use a human-readable date, for example July 25, 2026.',
            ],
            [
                'key' => 'field_vg_admin_first_notes',
                'label' => 'Admin-first workflow notes',
                'name' => 'vg_admin_first_notes',
                'type' => 'textarea',
                'rows' => 3,
                'instructions' => 'Briefly note what must be preserved during future updates: manual H1, Rank Math title, source review, photo choices, or custom blocks.',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'position' => 'side',
        'style' => 'default',
        'active' => true,
    ]);
}
add_action('acf/init', 'vg_register_admin_first_acf_fields');

function vg_public_editorial_author_name(): string
{
    return 'VietnamGuide editorial team';
}

function vg_is_forbidden_public_author_name(string $name): bool
{
    $normalized = strtolower(trim($name));

    return $normalized === '' || $normalized === 'administrator' || $normalized === 'admin';
}

function vg_rewrite_schema_author_value(mixed $value): mixed
{
    if (is_string($value)) {
        return vg_is_forbidden_public_author_name($value)
            ? vg_public_editorial_author_name()
            : $value;
    }

    if (! is_array($value)) {
        return $value;
    }

    if (array_is_list($value)) {
        return array_map('vg_rewrite_schema_author_value', $value);
    }

    $type = $value['@type'] ?? null;
    $is_person = $type === 'Person' || (is_array($type) && in_array('Person', $type, true));
    if ($is_person && array_key_exists('name', $value) && is_string($value['name']) && vg_is_forbidden_public_author_name($value['name'])) {
        $value['name'] = vg_public_editorial_author_name();
    }

    if (array_key_exists('author', $value)) {
        $value['author'] = vg_rewrite_schema_author_value($value['author']);
    }
    if (array_key_exists('@graph', $value) && is_array($value['@graph'])) {
        $value['@graph'] = vg_rewrite_schema_author_value($value['@graph']);
    }

    return $value;
}

function vg_filter_rank_math_json_ld(array $data): array
{
    $rewritten = vg_rewrite_schema_author_value($data);
    if (! is_array($rewritten)) {
        return $data;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $uri_path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
    if (empty($uri_path) && function_exists('get_queried_object_id')) {
        $qid = get_queried_object_id();
        if ($qid > 0) {
            $permalink = get_permalink($qid);
            if (is_string($permalink)) {
                $uri_path = strtolower(trim((string) parse_url($permalink, PHP_URL_PATH), '/'));
            }
        }
    }
    if (empty($uri_path) && isset($GLOBALS['post']) && is_object($GLOBALS['post'])) {
        $uri_path = strtolower((string) ($GLOBALS['post']->post_name ?? ''));
    }

    $entity_map = [
        'hanoi'         => ['@type' => 'City', 'name' => 'Hanoi', 'sameAs' => 'https://www.wikidata.org/wiki/Q1858'],
        'ho-chi-minh'   => ['@type' => 'City', 'name' => 'Ho Chi Minh City', 'sameAs' => 'https://www.wikidata.org/wiki/Q1854'],
        'ha-long'       => ['@type' => 'TouristAttraction', 'name' => 'Ha Long Bay', 'sameAs' => 'https://www.wikidata.org/wiki/Q190128'],
        'lan-ha'        => ['@type' => 'TouristAttraction', 'name' => 'Lan Ha Bay', 'sameAs' => 'https://www.wikidata.org/wiki/Q3216853'],
        'hoi-an'        => ['@type' => 'City', 'name' => 'Hoi An', 'sameAs' => 'https://www.wikidata.org/wiki/Q36167'],
        'da-nang'       => ['@type' => 'City', 'name' => 'Da Nang', 'sameAs' => 'https://www.wikidata.org/wiki/Q25282'],
        'hue'           => ['@type' => 'City', 'name' => 'Hue', 'sameAs' => 'https://www.wikidata.org/wiki/Q200257'],
        'sapa'          => ['@type' => 'City', 'name' => 'Sa Pa', 'sameAs' => 'https://www.wikidata.org/wiki/Q36384'],
        'ha-giang'      => ['@type' => 'AdministrativeArea', 'name' => 'Ha Giang', 'sameAs' => 'https://www.wikidata.org/wiki/Q36352'],
        'ninh-binh'     => ['@type' => 'AdministrativeArea', 'name' => 'Ninh Binh', 'sameAs' => 'https://www.wikidata.org/wiki/Q36359'],
        'phu-quoc'      => ['@type' => 'Place', 'name' => 'Phu Quoc', 'sameAs' => 'https://www.wikidata.org/wiki/Q223145'],
        'con-dao'       => ['@type' => 'Place', 'name' => 'Con Dao', 'sameAs' => 'https://www.wikidata.org/wiki/Q1118128'],
        'nha-trang'     => ['@type' => 'City', 'name' => 'Nha Trang', 'sameAs' => 'https://www.wikidata.org/wiki/Q19491'],
        'quy-nhon'      => ['@type' => 'City', 'name' => 'Quy Nhon', 'sameAs' => 'https://www.wikidata.org/wiki/Q26577'],
        'mui-ne'        => ['@type' => 'Place', 'name' => 'Mui Ne', 'sameAs' => 'https://www.wikidata.org/wiki/Q1333765'],
        'cat-ba'        => ['@type' => 'Place', 'name' => 'Cat Ba Island', 'sameAs' => 'https://www.wikidata.org/wiki/Q1936306'],
        'mekong'        => ['@type' => 'AdministrativeArea', 'name' => 'Mekong Delta', 'sameAs' => 'https://www.wikidata.org/wiki/Q1052867'],
        'phong-nha'     => ['@type' => 'NationalPark', 'name' => 'Phong Nha-Ke Bang', 'sameAs' => 'https://www.wikidata.org/wiki/Q11162'],
        'mu-cang-chai'  => ['@type' => 'AdministrativeArea', 'name' => 'Mu Cang Chai', 'sameAs' => 'https://www.wikidata.org/wiki/Q6930267'],
        'pu-luong'      => ['@type' => 'Place', 'name' => 'Pu Luong', 'sameAs' => 'https://www.wikidata.org/wiki/Q10808383'],
        'cu-chi'        => ['@type' => 'TouristAttraction', 'name' => 'Cu Chi Tunnels', 'sameAs' => 'https://www.wikidata.org/wiki/Q192935'],
        'trang-an'      => ['@type' => 'TouristAttraction', 'name' => 'Trang An', 'sameAs' => 'https://www.wikidata.org/wiki/Q10828551'],
        'tam-coc'       => ['@type' => 'TouristAttraction', 'name' => 'Tam Coc', 'sameAs' => 'https://www.wikidata.org/wiki/Q7680468'],
        'bai-tu-long'   => ['@type' => 'TouristAttraction', 'name' => 'Bai Tu Long Bay', 'sameAs' => 'https://www.wikidata.org/wiki/Q804153'],
        'cham-islands'  => ['@type' => 'Place', 'name' => 'Cham Islands', 'sameAs' => 'https://www.wikidata.org/wiki/Q10752538'],
        'ly-son'        => ['@type' => 'Place', 'name' => 'Ly Son Island', 'sameAs' => 'https://www.wikidata.org/wiki/Q10788329'],
        'old-quarter'   => ['@type' => 'TouristAttraction', 'name' => 'Old Quarter, Hanoi', 'sameAs' => 'https://www.wikidata.org/wiki/Q10808390'],
        'west-lake'     => ['@type' => 'TouristAttraction', 'name' => 'West Lake (Hanoi)', 'sameAs' => 'https://www.wikidata.org/wiki/Q3275095'],
        'french-quarter'=> ['@type' => 'TouristAttraction', 'name' => 'French Quarter, Hanoi', 'sameAs' => 'https://www.wikidata.org/wiki/Q10808390'],
        'north'         => ['@type' => 'AdministrativeArea', 'name' => 'Northern Vietnam', 'sameAs' => 'https://www.wikidata.org/wiki/Q10787579'],
        'central'       => ['@type' => 'AdministrativeArea', 'name' => 'Central Vietnam', 'sameAs' => 'https://www.wikidata.org/wiki/Q10787582'],
        'south'         => ['@type' => 'AdministrativeArea', 'name' => 'Southern Vietnam', 'sameAs' => 'https://www.wikidata.org/wiki/Q10787585'],
        'unesco'        => ['@type' => 'TouristAttraction', 'name' => 'World Heritage Sites in Vietnam', 'sameAs' => 'https://www.wikidata.org/wiki/Q1919934'],
        'ba-be'         => ['@type' => 'NationalPark', 'name' => 'Ba Be National Park', 'sameAs' => 'https://www.wikidata.org/wiki/Q1005391'],
        'ban-gioc'      => ['@type' => 'TouristAttraction', 'name' => 'Ban Gioc–Detian Falls', 'sameAs' => 'https://www.wikidata.org/wiki/Q1371758'],
        'can-tho'       => ['@type' => 'City', 'name' => 'Can Tho', 'sameAs' => 'https://www.wikidata.org/wiki/Q216075'],
        'ben-tre'       => ['@type' => 'AdministrativeArea', 'name' => 'Ben Tre Province', 'sameAs' => 'https://www.wikidata.org/wiki/Q36382'],
        'da-lat'        => ['@type' => 'City', 'name' => 'Da Lat', 'sameAs' => 'https://www.wikidata.org/wiki/Q25262'],
        'son-doong'     => ['@type' => 'TouristAttraction', 'name' => 'Hang Son Doong', 'sameAs' => 'https://www.wikidata.org/wiki/Q324021'],
    ];

    $matched_entities = [];
    foreach ($entity_map as $key => $ent) {
        if (strpos($uri_path, $key) !== false) {
            $matched_entities[] = $ent;
        }
    }

    $vietnam_entity = [
        '@type' => 'Country',
        'name' => 'Vietnam',
        'sameAs' => 'https://www.wikidata.org/wiki/Q881',
    ];

    $primary_about = $matched_entities !== [] ? $matched_entities[0] : $vietnam_entity;
    $mentions = array_merge([$vietnam_entity], $matched_entities);

    $speakable = [
        '@type' => 'SpeakableSpecification',
        'cssSelector' => ['.vg-concierge-verdict', '.vg-at-a-glance'],
    ];

    $has_graph = isset($rewritten['@graph']) && is_array($rewritten['@graph']);
    $nodes = &$rewritten;
    if ($has_graph) {
        $nodes = &$rewritten['@graph'];
    }

    foreach ($nodes as $node_key => &$node) {
        if (! is_array($node)) {
            continue;
        }
        $node_type = $node['@type'] ?? '';
        $is_article = $node_type === 'Article' || (is_array($node_type) && in_array('Article', $node_type, true));
        $is_webpage = $node_type === 'WebPage' || (is_array($node_type) && in_array('WebPage', $node_type, true));

        if ($is_article || $is_webpage) {
            if (! isset($node['about'])) {
                $node['about'] = $primary_about;
            }
            if (! isset($node['mentions'])) {
                $node['mentions'] = $mentions;
            }
            if ($is_article && ! isset($node['speakable'])) {
                $node['speakable'] = $speakable;
            }
        }
    }
    unset($node);

    return $rewritten;
}
add_filter('rank_math/json_ld', 'vg_filter_rank_math_json_ld', 99);

function vg_register_pattern_category(): void
{
    if (! function_exists('register_block_pattern_category')) {
        return;
    }

    if (class_exists('WP_Block_Pattern_Categories_Registry')) {
        $registry = WP_Block_Pattern_Categories_Registry::get_instance();

        if ($registry->is_registered('vietnamguide')) {
            return;
        }
    }

    register_block_pattern_category(
        'vietnamguide',
        ['label' => __('VietnamGuide', 'vietnamguide-core')]
    );
}
add_action('init', 'vg_register_pattern_category');

function vg_block_xmlrpc_request(): void
{
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
        status_header(403);
        exit;
    }
}
add_action('init', 'vg_block_xmlrpc_request', 0);
add_filter('xmlrpc_enabled', '__return_false');

function vg_remove_legacy_discovery_links(): void
{
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
}
add_action('init', 'vg_remove_legacy_discovery_links');

function vg_block_author_enumeration(): void
{
    if (is_admin()) {
        return;
    }

    $has_author_query = isset($_GET['author']) && preg_match('/^\d+$/', (string) $_GET['author']);

    if (! $has_author_query && ! is_author()) {
        return;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->set_404();
    }

    status_header(404);
    nocache_headers();
    exit;
}
add_action('template_redirect', 'vg_block_author_enumeration', 0);

function vg_disable_public_user_rest_endpoints(array $endpoints): array
{
    if (is_user_logged_in()) {
        return $endpoints;
    }

    unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);

    return $endpoints;
}
add_filter('rest_endpoints', 'vg_disable_public_user_rest_endpoints');

function vg_add_affiliate_link_attributes(string $content): string
{
    if (is_admin() || ! is_singular()) {
        return $content;
    }

    if (! class_exists('WP_HTML_Tag_Processor')) {
        return $content;
    }

    $processor = new WP_HTML_Tag_Processor($content);

    while ($processor->next_tag([
        'tag_name' => 'A',
        'class_name' => 'vg-affiliate-link',
    ])) {
        $rel_value = $processor->get_attribute('rel');
        $processor->set_attribute(
            'rel',
            vg_merge_affiliate_rel_tokens(is_string($rel_value) ? $rel_value : '')
        );
    }

    return $processor->get_updated_html();
}
add_filter('the_content', 'vg_add_affiliate_link_attributes', 20);

function vg_add_sitemap_to_robots(string $output, bool $public): string
{
    if (! $public || stripos($output, 'Sitemap:') !== false) {
        return $output;
    }

    return rtrim($output) . "\nSitemap: " . home_url('/sitemap_index.xml') . "\n";
}
add_filter('robots_txt', 'vg_add_sitemap_to_robots', 20, 2);

function vg_merge_affiliate_rel_tokens(string $rel_value): string
{
    $tokens = preg_split('/\s+/', trim($rel_value)) ?: [];
    $merged_tokens = [];
    $seen_tokens = [];

    foreach ($tokens as $token) {
        if ($token === '') {
            continue;
        }

        $token_key = strtolower($token);

        if (isset($seen_tokens[$token_key])) {
            continue;
        }

        $merged_tokens[] = $token;
        $seen_tokens[$token_key] = true;
    }

    foreach (['sponsored', 'nofollow'] as $required_token) {
        if (isset($seen_tokens[$required_token])) {
            continue;
        }

        $merged_tokens[] = $required_token;
        $seen_tokens[$required_token] = true;
    }

    return implode(' ', $merged_tokens);
}

function vg_register_image_sizes(): void
{
    add_image_size('vg-hero', 1920, 1080, true);
    add_image_size('vg-editorial-wide', 1440, 900, true);
    add_image_size('vg-card', 720, 540, true);
}
add_action('after_setup_theme', 'vg_register_image_sizes');
