<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

use Sugarcrm\Sugarcrm\custom\modules\Accounts\Services\AccountReadinessService;

/** Original tutorial example using only stock Accounts fields. */
class SdgAccountSummaryApi extends ModuleApi
{
    public function registerApiRest()
    {
        return [
            'sdgAccountSummary' => [
                'reqType' => 'GET',
                'noLoginRequired' => false,
                'path' => ['SDG', 'Accounts', '?', 'summary'],
                'pathVars' => ['', '', 'record', ''],
                'method' => 'getAccountSummary',
                'shortHelp' => 'Return a permission-checked account handoff summary',
            ],
        ];
    }

    public function getAccountSummary($api, $args)
    {
        $service = new AccountReadinessService();
        $fields = $service->getFieldNames();
        $args['module'] = 'Accounts';
        $args['fields'] = implode(',', $fields);
        $data = parent::retrieveRecord($api, $args);

        // Use only the data returned by Sugar's standard record API.
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = array_key_exists($field, $data)
                ? trim((string) ($data[$field] ?? ''))
                : null;
        }

        return [
            'schema_version' => 1,
            'account' => ['id' => $data['id'], 'name' => $values['name']],
            'contact' => ['phone' => $values['phone_office'], 'website' => $values['website']],
            'billing' => [
                'city' => $values['billing_address_city'],
                'country' => $values['billing_address_country'],
            ],
            'handoff' => $service->evaluate($data),
        ];
    }
}
