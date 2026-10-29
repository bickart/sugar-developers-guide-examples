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
        $args['fields'] = $fields;
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
