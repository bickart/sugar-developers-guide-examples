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

// Test only the custom mapping. Sugar's own retrieval is represented by a double.
require __DIR__ . '/fixtures/clients/base/api/ModuleApi.php';
require dirname(__DIR__) . '/custom/modules/Accounts/clients/base/api/SdgAccountSummaryApi.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$api = new SdgAccountSummaryApi();
$route = $api->registerApiRest()['sdgAccountSummary'];
check($route['noLoginRequired'] === false && $route['reqType'] === 'GET', 'Authenticated GET route');
check($route['path'] === ['SDG', 'Accounts', '?', 'summary'], 'Separate route');
check($route['pathVars'] === ['', '', 'record', ''], 'Record mapping');
check($route['method'] === 'getAccountSummary', 'Handler registration');
$id = '11111111-2222-3333-4444-555555555555';
$fields = ['name', 'phone_office', 'website', 'billing_address_city', 'billing_address_country'];
$record = [
    'id' => $id,
    'name' => ' Example Account ',
    'phone_office' => '+44 20 7946 0123',
    'website' => 'https://example.com',
    'billing_address_city' => 'London',
    'billing_address_country' => 'United Kingdom',
    'description' => 'This field must not be returned.',
];
ModuleApi::$response = $record;
$invoke = fn ($args) => $api->getAccountSummary(null, $args);
$result = $invoke(['record' => $id, 'module' => 'Contacts', 'fields' => ['description']]);
check(ModuleApi::$calls === [[null, ['record' => $id, 'module' => 'Accounts', 'fields' => $fields]]], 'Delegate to built-in record API with fixed module and fields');
check($result === [
    'schema_version' => 1,
    'account' => ['id' => $id, 'name' => 'Example Account'],
    'contact' => ['phone' => '+44 20 7946 0123', 'website' => 'https://example.com'],
    'billing' => ['city' => 'London', 'country' => 'United Kingdom'],
    'handoff' => ['ready' => true, 'missing_fields' => []],
], 'Exact mapped response uses only selected fields from parent response');

// Let the parent decide whether a record ID is valid and which errors to return.
foreach ([[], ['record' => 'example-record-id'], ['record' => '']] as $args) {
    $invoke($args);
    $expected = $args;
    $expected['module'] = 'Accounts';
    $expected['fields'] = $fields;
    check(end(ModuleApi::$calls) === [null, $expected], 'No custom GUID format rule or replacement record');
}
foreach (['missing argument', 'record not found', 'record access denied'] as $message) {
    $error = new RuntimeException($message);
    ModuleApi::$error = $error;
    try {
        $invoke(['record' => $id]);
        throw new LogicException('Parent error was swallowed');
    } catch (RuntimeException $caught) {
        check($caught === $error, 'Return the original parent error');
    }
}
ModuleApi::$error = null;

foreach (['name', 'phone_office', 'billing_address_city', 'billing_address_country'] as $field) {
    ModuleApi::$response = $record;
    ModuleApi::$response[$field] = '   ';
    check($invoke(['record' => $id])['handoff'] === ['ready' => false, 'missing_fields' => [$field]], 'Visible blank field is missing: ' . $field);
    unset(ModuleApi::$response[$field]);
    $result = $invoke(['record' => $id]);
    check($result['handoff'] === ['ready' => null, 'missing_fields' => null], 'Do not infer data omitted by Sugar: ' . $field);
}
ModuleApi::$response = $record;
unset(ModuleApi::$response['website']);
$result = $invoke(['record' => $id]);
check($result['contact']['website'] === null && $result['handoff']['ready'] === true, 'Omitted optional field stays unknown without blocking readiness');
ModuleApi::$response = $record;
ModuleApi::$response['website'] = '';
$result = $invoke(['record' => $id]);
check($result['contact']['website'] === '' && $result['handoff']['ready'] === true, 'Visible optional blank stays blank');
ModuleApi::$response['phone_office'] = '0';
check($invoke(['record' => $id])['handoff']['ready'] === true, 'Zero is not confused with a blank');
echo "PASS: built-in API delegation, parent errors, response mapping, omitted values, and handoff rules.\n";
