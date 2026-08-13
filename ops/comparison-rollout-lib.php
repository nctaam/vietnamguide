<?php

declare(strict_types=1);

/**
 * Shared, deterministic comparison-rollout primitives.
 *
 * Approval failures intentionally return short reason codes. Secret material,
 * canonical payloads, HMACs, and WordPress salts never enter these results.
 */

function _vg_comparison_is_list(array $value): bool
{
    if ($value === []) {
        return true;
    }
    return array_keys($value) === range(0, count($value) - 1);
}

function _vg_comparison_canonicalize(mixed $value): mixed
{
    if (is_array($value)) {
        if (_vg_comparison_is_list($value)) {
            return array_map('_vg_comparison_canonicalize', $value);
        }

        $keys = array_keys($value);
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Canonical object keys must be strings.');
            }
        }
        sort($keys, SORT_STRING);
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = _vg_comparison_canonicalize($value[$key]);
        }
        return $result;
    }

    if (is_float($value) && !is_finite($value)) {
        throw new InvalidArgumentException('Non-finite numbers are not valid JSON.');
    }
    if (is_object($value) || is_resource($value)) {
        throw new InvalidArgumentException('Only JSON-compatible values can be canonicalized.');
    }
    return $value;
}

function vg_comparison_canonical_json(mixed $value): string
{
    return _vg_comparison_encode_canonical($value);
}

function _vg_comparison_encode_float(float $value): string
{
    if (!is_finite($value)) {
        throw new InvalidArgumentException('Non-finite numbers are not valid JSON.');
    }
    $encoded = strtolower(json_encode($value, JSON_THROW_ON_ERROR));
    if (preg_match('/^(-?\d+)(?:\.(\d+))?e([+-]?)(\d+)$/D', $encoded, $parts) !== 1) {
        return preg_replace('/\.0$/D', '', $encoded) ?? $encoded;
    }
    $fraction = isset($parts[2]) ? rtrim($parts[2], '0') : '';
    $mantissa = $parts[1] . ($fraction === '' ? '' : '.' . $fraction);
    $digits = ltrim($parts[4], '0');
    $digits = $digits === '' ? '0' : $digits;
    if ($parts[3] === '-' && strlen($digits) === 1) {
        $digits = '0' . $digits;
    }
    return $mantissa . 'e' . ($parts[3] === '-' ? '-' : '') . $digits;
}

function _vg_comparison_encode_canonical(mixed $value): string
{
    if (is_array($value)) {
        if (_vg_comparison_is_list($value)) {
            return '[' . implode(',', array_map('_vg_comparison_encode_canonical', $value)) . ']';
        }
        $keys = array_keys($value);
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Canonical object keys must be strings.');
            }
        }
        sort($keys, SORT_STRING);
        $pairs = [];
        foreach ($keys as $key) {
            $pairs[] = json_encode($key, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                . ':' . _vg_comparison_encode_canonical($value[$key]);
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
        return _vg_comparison_encode_float($value);
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if ($value === null) {
        return 'null';
    }
    throw new InvalidArgumentException('Only JSON-compatible values can be canonicalized.');
}

function vg_comparison_sha256(mixed $value): string
{
    return hash('sha256', vg_comparison_canonical_json($value));
}

function _vg_comparison_schema_type_matches(mixed $value, string $type): bool
{
    return match ($type) {
        'object' => is_array($value) && !_vg_comparison_is_list($value),
        'array' => is_array($value) && _vg_comparison_is_list($value),
        'string' => is_string($value),
        'integer' => is_int($value),
        'number' => is_int($value) || (is_float($value) && is_finite($value)),
        'boolean' => is_bool($value),
        'null' => $value === null,
        default => false,
    };
}

function _vg_comparison_json_equal(mixed $left, mixed $right): bool
{
    try {
        return vg_comparison_canonical_json($left) === vg_comparison_canonical_json($right);
    } catch (Throwable) {
        return false;
    }
}

function _vg_comparison_resolve_schema_ref(array $schema, string $ref): ?array
{
    if (!str_starts_with($ref, '#/')) {
        return null;
    }
    $node = $schema;
    foreach (explode('/', substr($ref, 2)) as $segment) {
        $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
        if (!array_key_exists($segment, $node) || !is_array($node[$segment])) {
            return null;
        }
        $node = $node[$segment];
    }
    return $node;
}

function _vg_comparison_string_length(string $value): int
{
    $matchCount = preg_match_all('/./us', $value, $unused);
    return $matchCount === false ? strlen($value) : $matchCount;
}

function _vg_comparison_validate_schema_node(mixed $value, array $node, array $root, string $pointer): array
{
    $errors = [];
    if (isset($node['$ref']) && is_string($node['$ref'])) {
        $resolved = _vg_comparison_resolve_schema_ref($root, $node['$ref']);
        return $resolved === null
            ? ["E_SCHEMA {$pointer}: unresolved local schema reference"]
            : _vg_comparison_validate_schema_node($value, $resolved, $root, $pointer);
    }

    foreach (['allOf' => 'all', 'anyOf' => 'any', 'oneOf' => 'one'] as $keyword => $mode) {
        if (!isset($node[$keyword]) || !is_array($node[$keyword])) {
            continue;
        }
        $passes = 0;
        foreach ($node[$keyword] as $subschema) {
            if (is_array($subschema) && _vg_comparison_validate_schema_node($value, $subschema, $root, $pointer) === []) {
                ++$passes;
            }
        }
        if (($mode === 'all' && $passes !== count($node[$keyword])) || ($mode === 'any' && $passes === 0) || ($mode === 'one' && $passes !== 1)) {
            $errors[] = "E_SCHEMA {$pointer}: {$keyword} constraint failed";
        }
    }

    if (array_key_exists('const', $node) && !_vg_comparison_json_equal($value, $node['const'])) {
        $errors[] = "E_SCHEMA {$pointer}: value does not match const";
    }
    if (isset($node['enum']) && is_array($node['enum'])) {
        $matched = false;
        foreach ($node['enum'] as $candidate) {
            if (_vg_comparison_json_equal($value, $candidate)) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $errors[] = "E_SCHEMA {$pointer}: value is outside enum";
        }
    }

    if (isset($node['type'])) {
        $types = is_array($node['type']) ? $node['type'] : [$node['type']];
        $typeMatch = false;
        foreach ($types as $type) {
            if (is_string($type) && _vg_comparison_schema_type_matches($value, $type)) {
                $typeMatch = true;
                break;
            }
        }
        if (!$typeMatch) {
            $errors[] = "E_SCHEMA {$pointer}: type constraint failed";
            return $errors;
        }
    }

    if (is_array($value) && !_vg_comparison_is_list($value)) {
        $properties = isset($node['properties']) && is_array($node['properties']) ? $node['properties'] : [];
        foreach (($node['required'] ?? []) as $required) {
            if (is_string($required) && !array_key_exists($required, $value)) {
                $errors[] = "E_SCHEMA {$pointer}: missing required property {$required}";
            }
        }
        if (($node['additionalProperties'] ?? true) === false) {
            foreach (array_keys($value) as $key) {
                if (!array_key_exists($key, $properties)) {
                    $errors[] = "E_SCHEMA {$pointer}: unknown property {$key}";
                }
            }
        }
        foreach ($properties as $key => $propertySchema) {
            if (array_key_exists($key, $value) && is_array($propertySchema)) {
                $escaped = str_replace(['~', '/'], ['~0', '~1'], (string) $key);
                array_push($errors, ..._vg_comparison_validate_schema_node($value[$key], $propertySchema, $root, "{$pointer}/{$escaped}"));
            }
        }
    }

    if (is_array($value) && _vg_comparison_is_list($value)) {
        $count = count($value);
        if (isset($node['minItems']) && $count < (int) $node['minItems']) {
            $errors[] = "E_SCHEMA {$pointer}: array has too few items";
        }
        if (isset($node['maxItems']) && $count > (int) $node['maxItems']) {
            $errors[] = "E_SCHEMA {$pointer}: array has too many items";
        }
        if (($node['uniqueItems'] ?? false) === true) {
            $seen = [];
            foreach ($value as $item) {
                $encoded = vg_comparison_canonical_json($item);
                if (isset($seen[$encoded])) {
                    $errors[] = "E_SCHEMA {$pointer}: array items are not unique";
                    break;
                }
                $seen[$encoded] = true;
            }
        }
        if (isset($node['items']) && is_array($node['items'])) {
            foreach ($value as $index => $item) {
                array_push($errors, ..._vg_comparison_validate_schema_node($item, $node['items'], $root, "{$pointer}/{$index}"));
            }
        }
    }

    if (is_string($value)) {
        $length = _vg_comparison_string_length($value);
        if (isset($node['minLength']) && $length < (int) $node['minLength']) {
            $errors[] = "E_SCHEMA {$pointer}: string is too short";
        }
        if (isset($node['maxLength']) && $length > (int) $node['maxLength']) {
            $errors[] = "E_SCHEMA {$pointer}: string is too long";
        }
        if (isset($node['pattern']) && @preg_match('~' . str_replace('~', '\\~', (string) $node['pattern']) . '~D', $value) !== 1) {
            $errors[] = "E_SCHEMA {$pointer}: pattern constraint failed";
        }
        if (($node['format'] ?? '') === 'date-time' && !_vg_comparison_is_strict_utc($value)) {
            $errors[] = "E_SCHEMA {$pointer}: date-time format failed";
        }
    }

    if ((is_int($value) || is_float($value)) && isset($node['minimum']) && $value < $node['minimum']) {
        $errors[] = "E_SCHEMA {$pointer}: value is below minimum";
    }
    if ((is_int($value) || is_float($value)) && isset($node['maximum']) && $value > $node['maximum']) {
        $errors[] = "E_SCHEMA {$pointer}: value is above maximum";
    }
    return $errors;
}

function vg_comparison_validate_schema(mixed $value, array $schema, string $pointer = '$'): array
{
    $root = $schema;
    if (isset($schema['$defs'], $schema['$ref']) && is_array($schema['$defs']) && is_string($schema['$ref'])) {
        $resolved = _vg_comparison_resolve_schema_ref($schema, $schema['$ref']);
        if ($resolved === null) {
            return ["E_SCHEMA {$pointer}: unresolved local schema reference"];
        }
        return _vg_comparison_validate_schema_node($value, $resolved, $root, $pointer);
    }
    return _vg_comparison_validate_schema_node($value, $schema, $root, $pointer);
}

function _vg_comparison_is_strict_utc(string $value): bool
{
    if (preg_match('/^\d{4}-(0[1-9]|1[0-2])-([0-2]\d|3[01])T([01]\d|2[0-3]):[0-5]\d:[0-5]\dZ$/D', $value) !== 1) {
        return false;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i:s\\Z', $value, new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    return $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        && $date->format('Y-m-d\\TH:i:s\\Z') === $value;
}

function _vg_comparison_fail(string $reason): array
{
    return ['ok' => false, 'reason' => $reason];
}

function _vg_comparison_key_environment(string $keyId): ?string
{
    return match ($keyId) {
        'comparison-approval-2026-01' => 'VG_COMPARISON_APPROVAL_SECRET_2026_01',
        default => null,
    };
}

function _vg_comparison_wordpress_secret_values(): array
{
    $values = [];
    foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $name) {
        if (defined($name)) {
            $value = constant($name);
            if (is_string($value) && $value !== '') {
                $values[] = $value;
            }
        }
    }
    if (function_exists('wp_salt')) {
        foreach (['auth', 'secure_auth', 'logged_in', 'nonce'] as $scheme) {
            $value = wp_salt($scheme);
            if (is_string($value) && $value !== '') {
                $values[] = $value;
            }
        }
    }
    return $values;
}

function _vg_comparison_sorted_unique_change_ids(mixed $value): bool
{
    if (!is_array($value) || !_vg_comparison_is_list($value) || $value === [] || count($value) > 64) {
        return false;
    }
    $prior = null;
    foreach ($value as $item) {
        if (!_vg_comparison_stable_id($item)) {
            return false;
        }
        if ($prior !== null && strcmp($prior, $item) >= 0) {
            return false;
        }
        $prior = $item;
    }
    return true;
}

function _vg_comparison_stable_id(mixed $value): bool
{
    return is_string($value) && strlen($value) >= 3 && strlen($value) <= 64
        && preg_match('/^[a-z0-9][a-z0-9._-]*$/D', $value) === 1;
}

function _vg_comparison_safe_text(mixed $value): bool
{
    return is_string($value) && $value !== '' && strlen($value) <= 2000
        && preg_match('/[\x00-\x1F\x7F<>]/', $value) !== 1
        && preg_match('/(?:javascript:|data:text\/html|(?:^|\s)on[a-zA-Z]+\s*=)/i', $value) !== 1;
}

function _vg_comparison_page_path(mixed $value): bool
{
    return is_string($value) && strlen($value) >= 3 && strlen($value) <= 160
        && preg_match('/^[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?(?:\/[a-z0-9]+(?:[a-z0-9-]*[a-z0-9])?)*$/D', $value) === 1;
}

function _vg_comparison_approval_shape_valid(array $artifact): bool
{
    $required = ['artifact_version', 'manifest_hash', 'identity_id', 'wp_user_id', 'role', 'timestamp_utc', 'change_ids', 'change_reason', 'key_id', 'hmac_sha256'];
    $keys = array_keys($artifact);
    sort($keys, SORT_STRING);
    $expected = $required;
    sort($expected, SORT_STRING);
    if ($keys !== $expected) {
        return false;
    }
    return $artifact['artifact_version'] === '1'
        && is_string($artifact['manifest_hash']) && preg_match('/^[a-f0-9]{64}$/D', $artifact['manifest_hash']) === 1
        && _vg_comparison_stable_id($artifact['identity_id'])
        && is_int($artifact['wp_user_id']) && $artifact['wp_user_id'] >= 1
        && in_array($artifact['role'], ['author', 'reviewer'], true)
        && is_string($artifact['timestamp_utc']) && _vg_comparison_is_strict_utc($artifact['timestamp_utc'])
        && _vg_comparison_sorted_unique_change_ids($artifact['change_ids'])
        && in_array($artifact['change_reason'], ['source_refresh', 'operational_change', 'decision_change', 'route_change', 'correction'], true)
        && _vg_comparison_stable_id($artifact['key_id'])
        && is_string($artifact['hmac_sha256']) && preg_match('/^[a-f0-9]{64}$/D', $artifact['hmac_sha256']) === 1;
}

function _vg_comparison_find_identity(array $registry, string $identityId): array
{
    if (!isset($registry['identities']) || !is_array($registry['identities'])) {
        return ['ok' => false];
    }
    $found = null;
    $registryKeys = array_keys($registry);
    sort($registryKeys, SORT_STRING);
    if ($registryKeys !== ['identities'] && $registryKeys !== ['generated_on', 'identities', 'registry_version']) {
        return ['ok' => false];
    }
    if (count($registry['identities']) < 1 || count($registry['identities']) > 50) {
        return ['ok' => false];
    }
    $identityIds = [];
    $wpUserIds = [];
    foreach ($registry['identities'] as $identity) {
        if (!is_array($identity)
            || !isset($identity['identity_id'], $identity['wp_user_id'], $identity['display_name'], $identity['public_profile_path'], $identity['roles'])
            || count(array_keys($identity)) !== 5
            || array_diff(array_keys($identity), ['identity_id', 'wp_user_id', 'display_name', 'public_profile_path', 'roles']) !== []
            || !_vg_comparison_stable_id($identity['identity_id'])
            || !is_int($identity['wp_user_id']) || $identity['wp_user_id'] < 1
            || !_vg_comparison_safe_text($identity['display_name'])
            || !_vg_comparison_page_path($identity['public_profile_path'])
            || !is_array($identity['roles']) || !_vg_comparison_is_list($identity['roles']) || $identity['roles'] === [] || count($identity['roles']) > 2
            || count(array_unique($identity['roles'], SORT_STRING)) !== count($identity['roles'])
            || array_diff($identity['roles'], ['author', 'reviewer']) !== []) {
            return ['ok' => false];
        }
        if (isset($identityIds[$identity['identity_id']]) || isset($wpUserIds[$identity['wp_user_id']])) {
            return ['ok' => false];
        }
        $identityIds[$identity['identity_id']] = true;
        $wpUserIds[$identity['wp_user_id']] = true;
        if ($identity['identity_id'] === $identityId) {
            $found = $identity;
        }
    }
    return $found === null ? ['ok' => false] : ['ok' => true, 'identity' => $found];
}

function _vg_comparison_wordpress_identity_active(array $identity): bool
{
    if (!function_exists('get_userdata')) {
        return false;
    }
    $user = get_userdata((int) ($identity['wp_user_id'] ?? 0));
    if (!is_object($user) || (class_exists('WP_User') && !$user instanceof WP_User)) {
        return false;
    }
    if ((int) ($user->ID ?? 0) !== (int) $identity['wp_user_id'] || (int) ($user->user_status ?? 1) !== 0) {
        return false;
    }
    if (!empty($user->spam) || !empty($user->deleted) || !is_array($user->roles ?? null) || $user->roles === []) {
        return false;
    }
    if ((string) ($user->display_name ?? '') !== (string) ($identity['display_name'] ?? '')) {
        return false;
    }
    // Reviewer authority excludes WordPress author/contributor/subscriber roles.
    $wordpressRoleAllowlist = [
        'author' => ['administrator', 'editor', 'author'],
        'reviewer' => ['administrator', 'editor'],
    ];
    foreach ($identity['roles'] as $editorialRole) {
        if (!isset($wordpressRoleAllowlist[$editorialRole])) {
            return false;
        }
        $authorized = false;
        foreach ($user->roles as $wordpressRole) {
            if (is_string($wordpressRole) && in_array($wordpressRole, $wordpressRoleAllowlist[$editorialRole], true)) {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            return false;
        }
    }
    return true;
}

function vg_comparison_verify_approval(array $artifact, array $identity_registry, string $manifest_hash): array
{
    if (!_vg_comparison_approval_shape_valid($artifact)) {
        return _vg_comparison_fail('E_APPROVAL_SHAPE');
    }
    if (preg_match('/^[a-f0-9]{64}$/D', $manifest_hash) !== 1 || !hash_equals($manifest_hash, $artifact['manifest_hash'])) {
        return _vg_comparison_fail('E_APPROVAL_MANIFEST');
    }
    $environment = _vg_comparison_key_environment($artifact['key_id']);
    if ($environment === null) {
        return _vg_comparison_fail('E_APPROVAL_KEY');
    }
    $secret = getenv($environment);
    if (!is_string($secret) || $secret === '' || in_array($secret, _vg_comparison_wordpress_secret_values(), true)) {
        return _vg_comparison_fail('E_APPROVAL_SECRET');
    }
    $lookup = _vg_comparison_find_identity($identity_registry, $artifact['identity_id']);
    if (($lookup['ok'] ?? false) !== true) {
        return _vg_comparison_fail('E_APPROVAL_IDENTITY');
    }
    $identity = $lookup['identity'];
    if (!isset($identity['wp_user_id'], $identity['display_name'], $identity['roles'])
        || !is_int($identity['wp_user_id'])
        || $identity['wp_user_id'] !== $artifact['wp_user_id']
        || !is_string($identity['display_name'])
        || !is_array($identity['roles'])
        || $identity['roles'] === []
        || !in_array($artifact['role'], $identity['roles'], true)
        || !_vg_comparison_wordpress_identity_active($identity)) {
        return _vg_comparison_fail('E_APPROVAL_AUTHORIZATION');
    }
    $payload = $artifact;
    unset($payload['hmac_sha256']);
    $expected = hash_hmac('sha256', vg_comparison_canonical_json($payload), $secret);
    if (!hash_equals($expected, $artifact['hmac_sha256'])) {
        return _vg_comparison_fail('E_APPROVAL_HMAC');
    }
    return ['ok' => true, 'artifact_hash' => vg_comparison_sha256($artifact), 'identity_id' => $artifact['identity_id'], 'wp_user_id' => $artifact['wp_user_id'], 'role' => $artifact['role']];
}

function _vg_comparison_requires_four_eyes(array $impact): bool
{
    if (($impact['change_reason'] ?? '') === 'decision_change') {
        return true;
    }
    foreach (['outcomes_changed', 'primary_outcome_changed', 'hard_constraints_changed', 'negative_recommendations_changed', 'archetype_changed', 'decisive_axes_changed'] as $field) {
        if (($impact[$field] ?? false) === true) {
            return true;
        }
    }
    return false;
}

function _vg_comparison_verify_approval_set(array $artifacts, array $identity_registry, string $manifest_hash, array $impact): array
{
    $allowedImpactKeys = ['change_reason', 'change_ids', 'outcomes_changed', 'primary_outcome_changed', 'hard_constraints_changed', 'negative_recommendations_changed', 'archetype_changed', 'decisive_axes_changed'];
    if ($artifacts === [] || array_diff(array_keys($impact), $allowedImpactKeys) !== []
        || !isset($impact['change_reason'], $impact['change_ids'])
        || !is_string($impact['change_reason'])
        || !in_array($impact['change_reason'], ['source_refresh', 'operational_change', 'decision_change', 'route_change', 'correction'], true)
        || !_vg_comparison_sorted_unique_change_ids($impact['change_ids'])) {
        return _vg_comparison_fail('E_APPROVAL_SET');
    }
    foreach (['outcomes_changed', 'primary_outcome_changed', 'hard_constraints_changed', 'negative_recommendations_changed', 'archetype_changed', 'decisive_axes_changed'] as $booleanField) {
        if (array_key_exists($booleanField, $impact) && !is_bool($impact[$booleanField])) {
            return _vg_comparison_fail('E_APPROVAL_SET');
        }
    }
    $verified = [];
    $artifactHashes = [];
    foreach ($artifacts as $artifact) {
        if (!is_array($artifact)) {
            return _vg_comparison_fail('E_APPROVAL_SET');
        }
        $result = vg_comparison_verify_approval($artifact, $identity_registry, $manifest_hash);
        if (($result['ok'] ?? false) !== true) {
            return _vg_comparison_fail((string) ($result['reason'] ?? 'E_APPROVAL_SET'));
        }
        $artifactHash = $result['artifact_hash'];
        if (isset($artifactHashes[$artifactHash])) {
            return _vg_comparison_fail('E_APPROVAL_DUPLICATE');
        }
        $artifactHashes[$artifactHash] = true;
        $verified[] = $artifact;
    }

    $first = $verified[0];
    $expectedChangeIds = $impact['change_ids'];
    foreach ($verified as $artifact) {
        if ($artifact['change_reason'] !== $impact['change_reason']
            || $artifact['change_reason'] !== $first['change_reason']
            || !_vg_comparison_json_equal($artifact['change_ids'], $expectedChangeIds)
            || !_vg_comparison_json_equal($artifact['change_ids'], $first['change_ids'])
            || !hash_equals($artifact['manifest_hash'], $first['manifest_hash'])) {
            return _vg_comparison_fail('E_APPROVAL_SCOPE');
        }
    }

    $requiresFourEyes = _vg_comparison_requires_four_eyes($impact);
    $reviewerOnlyRefresh = $impact['change_reason'] === 'source_refresh'
        && ($impact['outcomes_changed'] ?? false) === false
        && !$requiresFourEyes;
    if ($reviewerOnlyRefresh) {
        return count($verified) === 1 && $verified[0]['role'] === 'reviewer'
            ? ['ok' => true, 'approval_count' => 1]
            : _vg_comparison_fail('E_APPROVAL_ROLES');
    }
    if (!$requiresFourEyes) {
        return count($verified) === 1 && $verified[0]['role'] === 'author'
            ? ['ok' => true, 'approval_count' => 1]
            : _vg_comparison_fail('E_APPROVAL_ROLES');
    }
    if (count($verified) !== 2) {
        return _vg_comparison_fail('E_APPROVAL_ROLES');
    }
    $byRole = [];
    foreach ($verified as $artifact) {
        if (isset($byRole[$artifact['role']])) {
            return _vg_comparison_fail('E_APPROVAL_ROLES');
        }
        $byRole[$artifact['role']] = $artifact;
    }
    if (!isset($byRole['author'], $byRole['reviewer'])) {
        return _vg_comparison_fail('E_APPROVAL_ROLES');
    }
    if ($byRole['author']['identity_id'] === $byRole['reviewer']['identity_id']
        || $byRole['author']['wp_user_id'] === $byRole['reviewer']['wp_user_id']) {
        return _vg_comparison_fail('E_APPROVAL_FOUR_EYES');
    }
    return ['ok' => true, 'approval_count' => 2];
}

function vg_comparison_compile_portfolio(array $manifest, array $sources, array $organizations, array $identities, array $schema): array
{
    return _vg_comparison_fail('E_NOT_IMPLEMENTED');
}

function vg_comparison_probe_source(array $source, array $limits): array
{
    return _vg_comparison_fail('E_NOT_IMPLEMENTED');
}

function vg_comparison_content_fingerprint(string $content): string
{
    return hash('sha256', $content);
}

function vg_comparison_insert_modules(string $content, array $requirements): array
{
    return _vg_comparison_fail('E_NOT_IMPLEMENTED');
}

function vg_comparison_render_metrics(string $html): array
{
    return ['utf8_bytes' => strlen($html), 'visible_characters' => _vg_comparison_string_length(strip_tags($html))];
}

function _vg_comparison_self_test(): bool
{
    $value = ['z' => 1, 'a' => ['b' => 2, 'a' => 1]];
    return vg_comparison_canonical_json($value) === '{"a":{"a":1,"b":2},"z":1}'
        && vg_comparison_sha256($value) === hash('sha256', '{"a":{"a":1,"b":2},"z":1}')
        && vg_comparison_canonical_json(['one' => 1.0, 'negative_zero' => -0.0, 'fraction' => 1.25]) === '{"fraction":1.25,"negative_zero":-0,"one":1}'
        && vg_comparison_canonical_json(['small' => 1.0e-7, 'negative' => -1.0e-7]) === '{"negative":-1e-07,"small":1e-07}';
}

if (PHP_SAPI === 'cli' && isset($argv) && realpath((string) ($argv[0] ?? '')) === __FILE__ && ($argv[1] ?? '') === '--self-test') {
    if (!_vg_comparison_self_test()) {
        fwrite(STDERR, "Comparison rollout PHP self-tests failed.\n");
        exit(1);
    }
    fwrite(STDOUT, "Comparison rollout PHP self-tests passed.\n");
    exit(0);
}
