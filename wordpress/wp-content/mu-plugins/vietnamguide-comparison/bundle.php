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
    return strlen($path) >= 3 && strlen($path) <= 160
        && preg_match('/^[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?(?:\/[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?)*$/D', $path) === 1;
}

function vg_comparison_bundle_string_length(string $value): int
{
    $count = preg_match_all('/./us', $value, $unused);
    return $count === false ? strlen($value) : $count;
}

function vg_comparison_bundle_stable_id(mixed $value, int $max = 64): bool
{
    return is_string($value)
        && vg_comparison_bundle_string_length($value) >= 3
        && vg_comparison_bundle_string_length($value) <= $max
        && preg_match('/^[a-z0-9][a-z0-9._-]*$/D', $value) === 1;
}

function vg_comparison_bundle_safe_text(mixed $value, int $max = 2000): bool
{
    return is_string($value)
        && vg_comparison_bundle_string_length($value) >= 1
        && vg_comparison_bundle_string_length($value) <= $max
        && preg_match('/[\x00-\x1F\x7F<>]/', $value) !== 1
        && preg_match('/(?:javascript:|data:text\/html)/i', $value) !== 1
        && preg_match('/(?:^|\s)on[a-z]+\s*=/i', $value) !== 1;
}

function vg_comparison_bundle_iso_date(mixed $value): bool
{
    if (!is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts) !== 1) {
        return false;
    }
    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
}

function vg_comparison_bundle_http_url(mixed $value): bool
{
    return is_string($value)
        && strlen($value) >= 10
        && strlen($value) <= 2048
        && preg_match('/^(?:https?):\/\/[^\x00-\x20\x7F<>"\']+$/D', $value) === 1;
}

function vg_comparison_bundle_canonical_domain(mixed $value): bool
{
    return is_string($value)
        && strlen($value) >= 3
        && strlen($value) <= 253
        && preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))+$/D', $value) === 1;
}

function vg_comparison_bundle_json_unique(array $value): bool
{
    $seen = [];
    foreach ($value as $item) {
        $key = vg_comparison_bundle_canonical_json($item);
        if (isset($seen[$key])) {
            return false;
        }
        $seen[$key] = true;
    }
    return true;
}

function vg_comparison_bundle_list(
    mixed $value,
    int $min,
    int $max,
    callable $validator,
    bool $unique = false
): bool {
    if (!is_array($value) || !vg_comparison_bundle_is_list($value) || count($value) < $min || count($value) > $max) {
        return false;
    }
    if ($unique && !vg_comparison_bundle_json_unique($value)) {
        return false;
    }
    foreach ($value as $item) {
        if (!$validator($item)) {
            return false;
        }
    }
    return true;
}

function vg_comparison_bundle_id_list(mixed $value, int $min, int $max, int $id_max = 64): bool
{
    return vg_comparison_bundle_list(
        $value,
        $min,
        $max,
        static fn(mixed $item): bool => vg_comparison_bundle_stable_id($item, $id_max),
        true
    );
}

function vg_comparison_bundle_rule_valid(mixed $rule, int $order_max): bool
{
    $keys = ['rule_id', 'order', 'kind', 'condition', 'outcome', 'context_tags', 'option_ids', 'claim_ids', 'axis_ids', 'outcome_id'];
    return is_array($rule)
        && vg_comparison_bundle_exact_keys($rule, $keys)
        && vg_comparison_bundle_stable_id($rule['rule_id'])
        && is_int($rule['order']) && $rule['order'] >= 1 && $rule['order'] <= $order_max
        && in_array($rule['kind'], ['hard_constraint', 'preference', 'tie_breaker'], true)
        && vg_comparison_bundle_safe_text($rule['condition'], 140)
        && vg_comparison_bundle_safe_text($rule['outcome'], 160)
        && vg_comparison_bundle_id_list($rule['context_tags'], 1, 8, 48)
        && vg_comparison_bundle_id_list($rule['option_ids'], 1, 4)
        && vg_comparison_bundle_id_list($rule['claim_ids'], 1, 12)
        && vg_comparison_bundle_id_list($rule['axis_ids'], 1, 6)
        && vg_comparison_bundle_stable_id($rule['outcome_id']);
}

function vg_comparison_bundle_source_mapping_valid(mixed $mapping): bool
{
    if (!is_array($mapping) || !vg_comparison_bundle_exact_keys($mapping, ['claim_id', 'option_id', 'axis_id', 'outcome_id'])) {
        return false;
    }
    foreach ($mapping as $value) {
        if (!vg_comparison_bundle_stable_id($value)) {
            return false;
        }
    }
    return true;
}

function vg_comparison_bundle_v2_shape_valid(array $bundle): bool
{
    if (!vg_comparison_bundle_exact_keys($bundle, vg_comparison_bundle_v2_required_keys())) {
        return false;
    }
    foreach (['manifest_version', 'source_registry_version', 'organization_registry_version', 'activation_artifact_version'] as $key) {
        if (!vg_comparison_bundle_stable_id($bundle[$key] ?? null)) {
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
        || !in_array($bundle['archetype'], ['competing_day_trips', 'city_or_heritage_base', 'coast_and_island', 'time_allocation', 'neighborhood', 'macro_region', 'attraction_and_landscape'], true)
        || !vg_comparison_bundle_safe_text($bundle['field_note'], 320)
        || !is_string($bundle['provenance_hash'])
        || preg_match('/^[a-f0-9]{64}$/D', $bundle['provenance_hash']) !== 1) {
        return false;
    }

    $editorial = $bundle['editorial'];
    if (!is_array($editorial)
        || !vg_comparison_bundle_exact_keys($editorial, ['reviewed_guide', 'written_by_identity_id', 'reviewed_by_identity_id', 'last_meaningful_update', 'update_summary', 'change_reason', 'affected_public_labels'])
        || !is_bool($editorial['reviewed_guide'])
        || !vg_comparison_bundle_stable_id($editorial['written_by_identity_id'])
        || !vg_comparison_bundle_stable_id($editorial['reviewed_by_identity_id'])
        || !vg_comparison_bundle_iso_date($editorial['last_meaningful_update'])
        || !vg_comparison_bundle_safe_text($editorial['update_summary'], 240)
        || !in_array($editorial['change_reason'], ['source_refresh', 'operational_change', 'decision_change', 'route_change', 'correction'], true)
        || !vg_comparison_bundle_list($editorial['affected_public_labels'], 1, 8, static fn(mixed $item): bool => vg_comparison_bundle_safe_text($item, 64), true)) {
        return false;
    }

    if (!vg_comparison_bundle_id_list($bundle['localities'], 1, 6)) {
        return false;
    }
    if (!vg_comparison_bundle_list($bundle['options'], 2, 4, static function (mixed $option): bool {
        return is_array($option)
            && vg_comparison_bundle_exact_keys($option, ['option_id', 'label', 'summary'])
            && vg_comparison_bundle_stable_id($option['option_id'])
            && vg_comparison_bundle_safe_text($option['label'], 48)
            && vg_comparison_bundle_safe_text($option['summary'], 180);
    }, true)) {
        return false;
    }

    $decision = $bundle['primary_decision'];
    if (!is_array($decision)
        || !vg_comparison_bundle_is_list($decision['rules'] ?? null)
        || !in_array($decision['outcome'] ?? null, ['option', 'no_clear_winner', 'combine_or_sequence'], true)) {
        return false;
    }
    $decision_keys = $decision['outcome'] === 'option'
        ? ['outcome_id', 'outcome', 'winner_option_id', 'summary', 'rules']
        : ['outcome_id', 'outcome', 'summary', 'rules'];
    if (!vg_comparison_bundle_exact_keys($decision, $decision_keys)
        || !vg_comparison_bundle_stable_id($decision['outcome_id'])
        || ($decision['outcome'] === 'option' && !vg_comparison_bundle_stable_id($decision['winner_option_id']))
        || !vg_comparison_bundle_safe_text($decision['summary'], 240)
        || !vg_comparison_bundle_list($decision['rules'], 1, 8, static fn(mixed $rule): bool => vg_comparison_bundle_rule_valid($rule, 8))) {
        return false;
    }
    if (!vg_comparison_bundle_list($bundle['evidence_moat'], 1, 4, static fn(mixed $item): bool => vg_comparison_bundle_safe_text($item, 240), true)) {
        return false;
    }

    if (!vg_comparison_bundle_list($bundle['axes'], 3, 6, static function (mixed $axis): bool {
        if (!is_array($axis) || !vg_comparison_bundle_exact_keys($axis, ['axis_id', 'label', 'explanation', 'claim_ids', 'option_ids', 'decisive', 'assessments', 'source_ids'])) {
            return false;
        }
        return vg_comparison_bundle_stable_id($axis['axis_id'])
            && vg_comparison_bundle_safe_text($axis['label'], 48)
            && vg_comparison_bundle_safe_text($axis['explanation'], 180)
            && vg_comparison_bundle_id_list($axis['claim_ids'], 1, 12)
            && vg_comparison_bundle_id_list($axis['option_ids'], 2, 4)
            && is_bool($axis['decisive'])
            && vg_comparison_bundle_list($axis['assessments'], 2, 4, static function (mixed $assessment): bool {
                return is_array($assessment)
                    && vg_comparison_bundle_exact_keys($assessment, ['option_id', 'outcome'])
                    && vg_comparison_bundle_stable_id($assessment['option_id'])
                    && vg_comparison_bundle_safe_text($assessment['outcome'], 160);
            })
            && vg_comparison_bundle_id_list($axis['source_ids'], 1, 5);
    }, true)) {
        return false;
    }

    if (!vg_comparison_bundle_list($bundle['traveler_lenses'], 3, 5, static function (mixed $lens): bool {
        if (!is_array($lens)) {
            return false;
        }
        $has_trade_off = array_key_exists('trade_off', $lens);
        $has_reversal = array_key_exists('reversal_condition', $lens);
        if (!$has_trade_off && !$has_reversal) {
            return false;
        }
        $keys = ['lens_id', 'traveler', 'context_tags', 'outcome_id', 'outcome', 'rule_path'];
        if ($has_trade_off) { $keys[] = 'trade_off'; }
        if ($has_reversal) { $keys[] = 'reversal_condition'; }
        return vg_comparison_bundle_exact_keys($lens, $keys)
            && vg_comparison_bundle_stable_id($lens['lens_id'])
            && vg_comparison_bundle_safe_text($lens['traveler'], 80)
            && vg_comparison_bundle_id_list($lens['context_tags'], 1, 8, 48)
            && vg_comparison_bundle_stable_id($lens['outcome_id'])
            && vg_comparison_bundle_safe_text($lens['outcome'], 220)
            && (!$has_trade_off || vg_comparison_bundle_safe_text($lens['trade_off'], 220))
            && (!$has_reversal || vg_comparison_bundle_safe_text($lens['reversal_condition'], 220))
            && vg_comparison_bundle_list($lens['rule_path'], 1, 5, static fn(mixed $rule): bool => vg_comparison_bundle_rule_valid($rule, 5));
    }, true)) {
        return false;
    }

    $source_classes = ['national_official', 'local_official', 'operational', 'conditions_heritage', 'independent_corroboration'];
    $claim_groups = ['access_transport', 'timing_duration', 'cost_booking', 'season_current_conditions', 'experience_fit', 'constraints_safety'];
    if (!vg_comparison_bundle_list($bundle['sources'], 6, 10, static function (mixed $source) use ($source_classes, $claim_groups): bool {
        $keys = ['source_id', 'publisher_id', 'publisher_name', 'organization_id', 'canonical_domain', 'source_class', 'title', 'url', 'checked_on', 'freshness_tier', 'freshness_state', 'localities', 'language', 'media_type', 'evidence_label', 'claim_groups', 'mappings'];
        if (!is_array($source) || !vg_comparison_bundle_exact_keys($source, $keys)) {
            return false;
        }
        return vg_comparison_bundle_stable_id($source['source_id'])
            && vg_comparison_bundle_stable_id($source['publisher_id'])
            && vg_comparison_bundle_safe_text($source['publisher_name'])
            && vg_comparison_bundle_stable_id($source['organization_id'])
            && vg_comparison_bundle_canonical_domain($source['canonical_domain'])
            && in_array($source['source_class'], $source_classes, true)
            && vg_comparison_bundle_safe_text($source['title'])
            && vg_comparison_bundle_http_url($source['url'])
            && in_array($source['evidence_label'], ['primary', 'corroborating', 'live_check_required'], true)
            && vg_comparison_bundle_iso_date($source['checked_on'])
            && in_array($source['freshness_tier'], ['live', 'current', 'stable'], true)
            && in_array($source['freshness_state'], ['current', 'stale', 'live_check_required'], true)
            && vg_comparison_bundle_id_list($source['localities'], 1, 12)
            && is_string($source['language']) && preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/D', $source['language']) === 1
            && in_array($source['media_type'], ['html', 'pdf', 'json'], true)
            && vg_comparison_bundle_list($source['claim_groups'], 1, 6, static fn(mixed $item): bool => in_array($item, $claim_groups, true), true)
            && vg_comparison_bundle_list($source['mappings'], 1, 24, 'vg_comparison_bundle_source_mapping_valid');
    }, true)) {
        return false;
    }

    $route_groups = [];
    if (!vg_comparison_bundle_list($bundle['related_routes'], 6, 8, static function (mixed $route) use (&$route_groups): bool {
        if (!is_array($route) || !vg_comparison_bundle_exact_keys($route, ['path', 'label', 'route_group'])) {
            return false;
        }
        if (!is_string($route['path']) || !vg_comparison_bundle_valid_path($route['path'])
            || !vg_comparison_bundle_safe_text($route['label'], 72)
            || !in_array($route['route_group'], ['deepen_place', 'build_route', 'check_practical'], true)) {
            return false;
        }
        $route_groups[$route['route_group']] = true;
        return true;
    }, true) || array_keys($route_groups) === []
        || !isset($route_groups['deepen_place'], $route_groups['build_route'], $route_groups['check_practical'])) {
        return false;
    }

    if (!vg_comparison_bundle_list($bundle['update_log'], 1, 12, static function (mixed $entry): bool {
        return is_array($entry)
            && vg_comparison_bundle_exact_keys($entry, ['date', 'summary', 'change_reason', 'affected_public_labels'])
            && vg_comparison_bundle_iso_date($entry['date'])
            && vg_comparison_bundle_safe_text($entry['summary'], 240)
            && in_array($entry['change_reason'], ['source_refresh', 'operational_change', 'decision_change', 'route_change', 'correction'], true)
            && vg_comparison_bundle_list($entry['affected_public_labels'], 1, 8, static fn(mixed $item): bool => vg_comparison_bundle_safe_text($item, 64), true);
    })) {
        return false;
    }

    $modules = $bundle['module_requirements'];
    if (!is_array($modules)
        || !vg_comparison_bundle_exact_keys($modules, ['validator_version', 'renderer_version', 'cache_key_fields', 'required_features'])
        || !vg_comparison_bundle_stable_id($modules['validator_version'])
        || !vg_comparison_bundle_stable_id($modules['renderer_version'])
        || $modules['cache_key_fields'] !== ['path', 'bundle_hash', 'schema_version', 'source_registry_version', 'activation_artifact_version']
        || !vg_comparison_bundle_list($modules['required_features'], 3, 6, static fn(mixed $item): bool => in_array($item, ['decision_frame', 'evidence_labels', 'source_checked_dates', 'related_routes', 'update_log', 'editorial_byline'], true), true)) {
        return false;
    }

    $render = $bundle['render_contract'];
    if (!is_array($render)
        || !vg_comparison_bundle_exact_keys($render, ['decision_heading', 'source_heading', 'route_heading', 'update_heading', 'page_language', 'max_visible_characters'])) {
        return false;
    }
    foreach (['decision_heading', 'source_heading', 'route_heading', 'update_heading'] as $key) {
        if (!vg_comparison_bundle_safe_text($render[$key], 72)) {
            return false;
        }
    }
    return is_string($render['page_language'])
        && preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/D', $render['page_language']) === 1
        && is_int($render['max_visible_characters'])
        && $render['max_visible_characters'] >= 1
        && $render['max_visible_characters'] <= 1800;
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
