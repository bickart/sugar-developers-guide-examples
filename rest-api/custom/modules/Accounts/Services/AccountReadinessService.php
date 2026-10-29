<?php

/*
 * Copyright (C) Amaiza LLC. - All Rights Reserved
 *
 * This source code is proprietary and confidential and protected under
 * international copyright law. All rights reserved and protected by
 * the copyright holders. This file is only available to authorized individuals
 * with the permission of the copyright holders. Unauthorized copying of this file,
 * via any medium is strictly prohibited. If you encounter this file and do not have
 * permission, please contact the copyright holders and delete this file.
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
