<?php

declare(strict_types=1);

function vg_comparison_bundle_v2_required_keys(): array
{
    return [
        'schema_version',
        'bundle_hash',
        'manifest_version',
        'source_registry_version',
        'organization_registry_version',
        'activation_artifact_version',
        'path',
        'post_id',
        'editorial',
        'archetype',
        'localities',
        'options',
        'primary_decision',
        'field_note',
        'evidence_moat',
        'axes',
        'traveler_lenses',
        'sources',
        'related_routes',
        'update_log',
        'module_requirements',
        'provenance_hash',
        'render_contract',
    ];
}

function vg_comparison_bundle_v1_required_keys(): array
{
    return ['schema_version', 'bundle_hash', 'path', 'post_id', 'title', 'decision', 'sources'];
}

function vg_comparison_bundle_is_list(array $value): bool
{
    return array_is_list($value);
}

function vg_comparison_bundle_canonical_json(mixed $value): string
{
    if (is_array($value)) {
        if (vg_comparison_bundle_is_list($value)) {
            return '[' . implode(',', array_map('vg_comparison_bundle_canonical_json', $value)) . ']';
        }
        $keys = array_keys($value);
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Object keys must be strings.');
            }
        }
        sort($keys, SORT_STRING);
        $pairs = [];
        foreach ($keys as $key) {
            $pairs[] = json_encode($key, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                . ':' . vg_comparison_bundle_canonical_json($value[$key]);
        }
        return '{' . implode(',', $pairs) . '}';
    }
    if (is_string($value)) {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
    if (is_int($value)) {
        return (string) $value;
    }
    if (is_float($value)) {
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Non-finite number.');
        }
        $encoded = strtolower(json_encode($value, JSON_THROW_ON_ERROR));
        return preg_replace('/\.0(?=e|$)/D', '', $encoded) ?? $encoded;
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if ($value === null) {
        return 'null';
    }
    throw new InvalidArgumentException('Non-JSON value.');
}

function vg_comparison_bundle_exact_keys(array $bundle, array $required): bool
{
    if (vg_comparison_bundle_is_list($bundle)) {
        return false;
    }
    $actual = array_keys($bundle);
    sort($actual, SORT_STRING);
    $expected = $required;
    sort($expected, SORT_STRING);
    return $actual === $expected;
}

function vg_comparison_bundle_valid_path(string $path): bool
{
    return strlen($path) <= 160
        && preg_match('/^[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?(?:\/[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?)*$/D', $path) === 1;
}

function vg_comparison_bundle_v2_shape_valid(array $bundle): bool
{
    if (!vg_comparison_bundle_exact_keys($bundle, vg_comparison_bundle_v2_required_keys())) {
        return false;
    }
    foreach (['manifest_version', 'source_registry_version', 'organization_registry_version', 'activation_artifact_version'] as $key) {
        if (!isset($bundle[$key]) || !is_string($bundle[$key]) || preg_match('/^[a-z0-9][a-z0-9._-]{2,63}$/D', $bundle[$key]) !== 1) {
            return false;
        }
    }
    if ($bundle['schema_version'] !== 'v' . VG_COMPARISON_BUNDLE_SCHEMA_CURRENT
        || !is_string($bundle['bundle_hash'])
        || preg_match('/^[a-f0-9]{64}$/D', $bundle['bundle_hash']) !== 1
        || !is_string($bundle['path'])
        || !vg_comparison_bundle_valid_path($bundle['path'])
        || !is_int($bundle['post_id'])
        || $bundle['post_id'] < 1
        || !is_string($bundle['archetype'])
        || !is_string($bundle['field_note'])
        || !is_string($bundle['provenance_hash'])
        || preg_match('/^[a-f0-9]{64}$/D', $bundle['provenance_hash']) !== 1) {
        return false;
    }
    foreach (['editorial', 'primary_decision', 'module_requirements', 'render_contract'] as $key) {
        if (!is_array($bundle[$key]) || vg_comparison_bundle_is_list($bundle[$key])) {
            return false;
        }
    }
    foreach (['localities', 'options', 'evidence_moat', 'axes', 'traveler_lenses', 'sources', 'related_routes', 'update_log'] as $key) {
        if (!is_array($bundle[$key]) || !vg_comparison_bundle_is_list($bundle[$key]) || $bundle[$key] === []) {
            return false;
        }
    }
    return true;
}

function vg_comparison_bundle_v1_shape_valid(array $bundle): bool
{
    return vg_comparison_bundle_exact_keys($bundle, vg_comparison_bundle_v1_required_keys())
        && $bundle['schema_version'] === 'v' . VG_COMPARISON_BUNDLE_SCHEMA_PREVIOUS
        && is_string($bundle['bundle_hash'])
        && preg_match('/^[a-f0-9]{64}$/D', $bundle['bundle_hash']) === 1
        && is_string($bundle['path'])
        && vg_comparison_bundle_valid_path($bundle['path'])
        && is_int($bundle['post_id'])
        && $bundle['post_id'] > 0
        && is_string($bundle['title'])
        && $bundle['title'] !== ''
        && is_string($bundle['decision'])
        && $bundle['decision'] !== ''
        && is_array($bundle['sources'])
        && vg_comparison_bundle_is_list($bundle['sources'])
        && count($bundle['sources']) >= 3;
}

function vg_comparison_migrate_bundle_v1_to_v2(array $bundle): array
{
    if (!vg_comparison_bundle_v1_shape_valid($bundle)) {
        return [];
    }
    // v1 lacks reviewed editorial, registry and evidence fields required by v2.
    return [];
}

function vg_comparison_bundle_duplicate_keys(string $json): bool
{
    try {
        json_decode($json, false, 128, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return false;
    }
    return vg_comparison_bundle_has_duplicate_key_tokens($json);
}

function vg_comparison_bundle_has_duplicate_key_tokens(string $json): bool
{
    $offset = 0;
    $length = strlen($json);
    $walk = null;
    $skip = static function () use ($json, $length, &$offset): void {
        while ($offset < $length && str_contains(" \t\r\n", $json[$offset])) {
            ++$offset;
        }
    };
    $readString = static function () use ($json, $length, &$offset): ?string {
        $start = $offset++;
        while ($offset < $length) {
            if ($json[$offset] === '\\') {
                $offset += 2;
            } elseif ($json[$offset++] === '"') {
                $value = json_decode(substr($json, $start, $offset - $start), true);
                return is_string($value) ? $value : null;
            }
        }
        return null;
    };
    $walk = static function () use (&$walk, $skip, $readString, $json, $length, &$offset): bool {
        $skip();
        if ($offset >= $length) {
            return false;
        }
        if ($json[$offset] === '{') {
            ++$offset;
            $keys = [];
            while ($offset < $length) {
                $skip();
                if ($json[$offset] === '}') {
                    ++$offset;
                    return false;
                }
                $key = $readString();
                if (!is_string($key) || isset($keys[$key])) {
                    return true;
                }
                $keys[$key] = true;
                $skip();
                if ($offset >= $length || $json[$offset++] !== ':') {
                    return false;
                }
                if ($walk()) {
                    return true;
                }
                $skip();
                if ($offset < $length && $json[$offset] === ',') {
                    ++$offset;
                    continue;
                }
            }
            return false;
        }
        if ($json[$offset] === '[') {
            ++$offset;
            while ($offset < $length) {
                $skip();
                if ($json[$offset] === ']') {
                    ++$offset;
                    return false;
                }
                if ($walk()) {
                    return true;
                }
                $skip();
                if ($offset < $length && $json[$offset] === ',') {
                    ++$offset;
                    continue;
                }
            }
            return false;
        }
        if ($json[$offset] === '"') {
            $readString();
            return false;
        }
        while ($offset < $length && !str_contains(",]} \t\r\n", $json[$offset])) {
            ++$offset;
        }
        return false;
    };
    return $walk();
}

function vg_comparison_log_bundle_hash_prefix(int $post_id): string
{
    $prefixes = $GLOBALS['vg_comparison_bundle_hash_prefixes'] ?? [];
    return is_array($prefixes) && isset($prefixes[$post_id]) && is_string($prefixes[$post_id])
        ? $prefixes[$post_id]
        : 'none';
}

function vg_comparison_bundle_reject(string $code, int $post_id): array
{
    if (function_exists('vg_comparison_log_rejection')) {
        vg_comparison_log_rejection($code, $post_id);
    }
    return ['ok' => false, 'reason' => $code];
}

function vg_comparison_bundle_expectations(string $path): array
{
    if (!is_callable('vg_comparison_activation_snapshot')
        || !is_callable('vg_comparison_organization_registry_version')) {
        return [];
    }
    $snapshot = vg_comparison_activation_snapshot();
    $organization_version = vg_comparison_organization_registry_version();
    if (!is_array($snapshot)
        || !is_string($organization_version)
        || $organization_version === ''
        || !isset($snapshot['manifest_version'], $snapshot['activation_artifact_version'], $snapshot['paths'], $snapshot['bundle_hashes'])
        || !is_string($snapshot['manifest_version'])
        || !is_string($snapshot['activation_artifact_version'])
        || !is_array($snapshot['paths'])
        || !in_array($path, $snapshot['paths'], true)
        || !is_array($snapshot['bundle_hashes'])) {
        return [];
    }
    foreach ($snapshot['bundle_hashes'] as $entry) {
        if (is_array($entry) && ($entry['path'] ?? null) === $path) {
            foreach (['bundle_hash', 'schema_version', 'source_registry_version', 'activation_artifact_version'] as $key) {
                if (!isset($entry[$key]) || !is_string($entry[$key]) || $entry[$key] === '') {
                    return [];
                }
            }
            return [
                'manifest_version' => $snapshot['manifest_version'],
                'activation_artifact_version' => $snapshot['activation_artifact_version'],
                'organization_registry_version' => $organization_version,
                'entry' => $entry,
            ];
        }
    }
    return [];
}

function vg_comparison_load_bundle(WP_Post|int $post): array
{
    static $cache = [];
    $post_id = is_int($post) ? $post : $post->ID;
    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    try {
        $post_object = $post instanceof WP_Post ? $post : (function_exists('get_post') ? get_post($post) : false);
        if (!$post_object instanceof WP_Post || $post_object->ID !== $post_id || $post_id < 1) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_POST', max(0, $post_id));
        }
        $path = is_callable('vg_get_guide_path')
            ? vg_get_guide_path($post_object)
            : (function_exists('get_page_uri') ? get_page_uri($post_object) : '');
        if (!is_string($path) || !vg_comparison_bundle_valid_path($path)) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_PATH', $post_id);
        }
        if (!is_callable('vg_is_comparison_rollout_active_path') || !vg_is_comparison_rollout_active_path($path)) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_INACTIVE', $post_id);
        }

        $raw = get_post_meta($post_id, 'vg_eeat_comparison_bundle', true);
        if (!is_string($raw) || $raw === '') {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_META', $post_id);
        }
        if (strlen($raw) > 65536) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_SIZE', $post_id);
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_BOM', $post_id);
        }
        if (!preg_match('//u', $raw)) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_UTF8', $post_id);
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $raw)) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_CONTROL', $post_id);
        }
        if (vg_comparison_bundle_duplicate_keys($raw)) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_JSON_DUPLICATE', $post_id);
        }
        try {
            $bundle = json_decode($raw, true, 128, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_JSON', $post_id);
        }
        if (!is_array($bundle) || vg_comparison_bundle_is_list($bundle)) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_SCHEMA', $post_id);
        }
        $schema_version = $bundle['schema_version'] ?? null;
        if (!is_string($schema_version)
            || !in_array($schema_version, ['v' . VG_COMPARISON_BUNDLE_SCHEMA_CURRENT, 'v' . VG_COMPARISON_BUNDLE_SCHEMA_PREVIOUS], true)) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_VERSION', $post_id);
        }
        $valid_shape = $schema_version === 'v' . VG_COMPARISON_BUNDLE_SCHEMA_CURRENT
            ? vg_comparison_bundle_v2_shape_valid($bundle)
            : vg_comparison_bundle_v1_shape_valid($bundle);
        if (!$valid_shape) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_SCHEMA', $post_id);
        }
        if ($bundle['path'] !== $path) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_PATH', $post_id);
        }
        if ($bundle['post_id'] !== $post_id) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_POST_ID', $post_id);
        }

        $bundle_hash = $bundle['bundle_hash'];
        $GLOBALS['vg_comparison_bundle_hash_prefixes'][$post_id] = substr($bundle_hash, 0, 8);
        $hashable = $bundle;
        unset($hashable['bundle_hash']);
        if (!hash_equals($bundle_hash, hash('sha256', vg_comparison_bundle_canonical_json($hashable)))) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_BUNDLE_HASH', $post_id);
        }
        $expected = vg_comparison_bundle_expectations($path);
        if ($expected === []) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_ACTIVATION', $post_id);
        }
        if ($expected['activation_artifact_version'] !== VG_COMPARISON_ACTIVATION_ARTIFACT_VERSION
            || $expected['entry']['activation_artifact_version'] !== VG_COMPARISON_ACTIVATION_ARTIFACT_VERSION) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_ACTIVATION_VERSION', $post_id);
        }
        if ($expected['entry']['bundle_hash'] !== $bundle_hash
            || $expected['entry']['schema_version'] !== $schema_version) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_BUNDLE_HASH', $post_id);
        }
        if ($schema_version === 'v' . VG_COMPARISON_BUNDLE_SCHEMA_PREVIOUS) {
            $bundle = vg_comparison_migrate_bundle_v1_to_v2($bundle);
            if ($bundle === [] || !vg_comparison_bundle_v2_shape_valid($bundle)) {
                return $cache[$post_id] = vg_comparison_bundle_reject('E_MIGRATION', $post_id);
            }
            $schema_version = $bundle['schema_version'];
            $bundle_hash = $bundle['bundle_hash'];
        }
        if ($bundle['activation_artifact_version'] !== VG_COMPARISON_ACTIVATION_ARTIFACT_VERSION) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_ACTIVATION_VERSION', $post_id);
        }
        if ($bundle['manifest_version'] !== $expected['manifest_version']) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_MANIFEST_VERSION', $post_id);
        }
        if ($bundle['source_registry_version'] !== $expected['entry']['source_registry_version']) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_SOURCE_REGISTRY_VERSION', $post_id);
        }
        if ($bundle['organization_registry_version'] !== $expected['organization_registry_version']) {
            return $cache[$post_id] = vg_comparison_bundle_reject('E_ORGANIZATION_REGISTRY_VERSION', $post_id);
        }
        $activation_version = $bundle['activation_artifact_version'];
        return $cache[$post_id] = [
            'ok' => true,
            'bundle' => $bundle,
            'bundle_hash' => $bundle_hash,
            'cache_key' => implode(':', [
                $path,
                $bundle_hash,
                $schema_version,
                $bundle['source_registry_version'],
                $bundle['organization_registry_version'],
                $activation_version,
            ]),
        ];
    } catch (Throwable) {
        return $cache[$post_id] = vg_comparison_bundle_reject('E_RUNTIME', max(0, $post_id));
    }
}
