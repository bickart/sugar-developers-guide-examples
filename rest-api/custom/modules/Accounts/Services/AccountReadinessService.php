<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Sugarcrm\Sugarcrm\custom\modules\Accounts\Services;

/** Evaluates account readiness using only values supplied by its caller. */
final class AccountReadinessService
{
    private const FIELD_DEFINITIONS = [
        'name' => ['required' => true],
        'phone_office' => ['required' => true],
        'website' => ['required' => false],
        'billing_address_city' => ['required' => true],
        'billing_address_country' => ['required' => true],
    ];

    public function getFieldNames(): array
    {
        return array_keys(self::FIELD_DEFINITIONS);
    }

    /** A missing key is unknown; a returned null or blank value is missing data. */
    public function evaluate(array $data): array
    {
        $missing = [];
        $canCheck = true;

        foreach (self::FIELD_DEFINITIONS as $field => $definition) {
            if (!$definition['required']) {
                continue;
            }
            if (!array_key_exists($field, $data)) {
                $canCheck = false;
            } elseif (trim((string) ($data[$field] ?? '')) === '') {
                $missing[] = $field;
            }
        }

        return [
            'ready' => $canCheck ? $missing === [] : null,
            'missing_fields' => $canCheck ? $missing : null,
        ];
    }
}
