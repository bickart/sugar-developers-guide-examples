<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

// Test the API and real service together, with a stand-in for Sugar's retrieval.
require __DIR__ . '/fixtures/clients/base/api/ModuleApi.php';
require dirname(__DIR__) . '/custom/modules/Accounts/Services/AccountReadinessService.php';
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
check(ModuleApi::$calls === [[null, ['record' => $id, 'module' => 'Accounts', 'fields' => implode(',', $fields)]]], 'Delegate to built-in record API with fixed module and comma-separated fields');
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
    $expected['fields'] = implode(',', $fields);
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

// Verify that the API passes omitted values through to the real service.
ModuleApi::$response = $record;
unset(ModuleApi::$response['billing_address_country']);
$result = $invoke(['record' => $id]);
check($result['billing']['country'] === null, 'Omitted field remains null in the response');
check($result['handoff'] === ['ready' => null, 'missing_fields' => null], 'The service receives omitted required fields unchanged');
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
