<?php
/**
 * Copyright © DMLab. All rights reserved.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Model\Normalization;

use DmLab\AdminScim\Api\RequestNormalizerInterface;

/**
 * Google Workspace (Cloud Identity) provider-quirk normalizer for the
 * `admin-scim` server.
 *
 * Google's auto-provisioning SCIM client is broadly RFC-7644-compliant — closer
 * to Okta than to Entra — so this is deliberately thin. The one deviation it
 * absorbs is the `active` toggle Google uses to suspend/restore a user, which its
 * connector can serialize as a stringified boolean (`"true"`/`"false"`) rather
 * than a native JSON boolean. It rewrites those into real booleans on the `User`
 * resource — in the top-level body (POST/PUT) and inside PATCH operations (both
 * the `path: "active"` form and the path-less object form) — so the core sees
 * strict RFC shape. Groups pass through untouched (Google's group push is
 * standard SCIM), as does every payload that already carries a native boolean.
 *
 * Pure and total: any payload that isn't a stringified `active` on a User is
 * returned unchanged, and it never throws on unexpected input.
 */
class GoogleRequestNormalizer implements RequestNormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function normalize(string $resourceType, string $operation, array $payload): array
    {
        if ($resourceType !== self::RESOURCE_USER) {
            return $payload;
        }

        if (array_key_exists('active', $payload)) {
            $payload['active'] = $this->coerceActive($payload['active']);
        }

        if (strtoupper(trim($operation)) === 'PATCH') {
            $payload = $this->normalizePatch($payload);
        }

        return $payload;
    }

    /**
     * Coerce a stringified `active` inside each PATCH operation to a native bool.
     *
     * Handles both the targeted form (`path: "active"`, scalar value) and the
     * path-less form whose `value` is an attribute→value object carrying `active`.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function normalizePatch(array $payload): array
    {
        $key = array_key_exists('Operations', $payload)
            ? 'Operations'
            : (array_key_exists('operations', $payload) ? 'operations' : null);
        if ($key === null || !is_array($payload[$key]) || !array_is_list($payload[$key])) {
            return $payload;
        }

        $operations = [];
        foreach ($payload[$key] as $operation) {
            $operations[] = is_array($operation) ? $this->normalizeOperation($operation) : $operation;
        }
        $payload[$key] = $operations;

        return $payload;
    }

    /**
     * Normalize the `active` value carried by a single PATCH operation.
     *
     * @param array<string,mixed> $operation
     * @return array<string,mixed>
     */
    private function normalizeOperation(array $operation): array
    {
        $path = isset($operation['path']) && is_string($operation['path']) ? strtolower(trim($operation['path'])) : '';

        if ($path === 'active' && array_key_exists('value', $operation)) {
            $operation['value'] = $this->coerceActive($operation['value']);

            return $operation;
        }

        if ($path === '' && isset($operation['value']) && is_array($operation['value'])
            && !array_is_list($operation['value']) && array_key_exists('active', $operation['value'])) {
            $operation['value']['active'] = $this->coerceActive($operation['value']['active']);
        }

        return $operation;
    }

    /**
     * A stringified boolean becomes a native bool; anything else is left as-is.
     *
     * Total by design: a value already a bool, or a string that isn't a
     * recognizable boolean, passes through so the core can validate it.
     *
     * @param mixed $value
     * @return mixed
     */
    private function coerceActive(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $normalized = strtolower(trim($value));
        if ($normalized === 'true' || $normalized === '1') {
            return true;
        }
        if ($normalized === 'false' || $normalized === '0') {
            return false;
        }

        return $value;
    }
}
