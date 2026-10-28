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

// Isolated doubles exercise the example, not Sugar's authentication or team engine.
define('sugarEntry', true);
class SugarApi
{
}
class SugarApiExceptionInvalidParameter extends RuntimeException
{
}
class SugarApiExceptionNotFound extends RuntimeException
{
}
class SugarApiExceptionNotAuthorized extends RuntimeException
{
}
class BeanFactory
{
    public static $bean;
    public static array $calls = [];
    public static function retrieveBean(...$args)
    {
        self::$calls[] = $args;
        return self::$bean;
    }
}
class TestAccount
{
    public string $id = '11111111-2222-3333-4444-555555555555';
    public bool $canView = true;
    public array $denied = [];
    public array $readFields = [];
    public array $values = [
        'name' => ' Example Account ',
        'phone_office' => '+44 20 7946 0123',
        'website' => 'https://example.com',
        'billing_address_city' => 'London',
        'billing_address_country' => 'United Kingdom',
        'description' => 'This field must never be returned.',
    ];
    public function ACLAccess($action)
    {
        check($action === 'view', 'Record view permission checked');
        return $this->canView;
    }
    public function ACLFieldAccess($field, $action)
    {
        check($action === 'read', 'Field read permission checked');
        return !in_array($field, $this->denied, true);
    }
    public function __get($field)
    {
        $this->readFields[] = $field;
        return $this->values[$field] ?? null;
    }
    public function __isset($field)
    {
        return isset($this->values[$field]);
    }
}
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function expectException(string $type, callable $action): void
{
    try {
        $action();
    } catch (Throwable $error) {
        check($error instanceof $type, 'Expected ' . $type . ', got ' . get_class($error));
        return;
    }
    throw new RuntimeException('Expected ' . $type);
}

require dirname(__DIR__) . '/custom/clients/base/api/SdgAccountSummaryApi.php';
$api = new SdgAccountSummaryApi();
$route = $api->registerApiRest()['sdgAccountSummary'];
check($route['noLoginRequired'] === false && $route['reqType'] === 'GET', 'Authenticated GET route');
check($route['path'] === ['SDG', 'Accounts', '?', 'summary'], 'Unique route');
check($route['pathVars'] === ['', '', 'record', ''], 'Record mapping');
check($route['method'] === 'getAccountSummary', 'Handler registration');
$id = '11111111-2222-3333-4444-555555555555';
$invoke = fn ($args) => $api->getAccountSummary(null, $args);
foreach ([[], ['record' => ''], ['record' => '../Accounts'], ['record' => [$id]], ['record' => 123]] as $args) {
    expectException(SugarApiExceptionInvalidParameter::class, fn () => $invoke($args));
}
check(BeanFactory::$calls === [], 'Invalid input rejected before retrieval');
foreach ([null, false, (object) ['id' => '']] as $missingBean) {
    BeanFactory::$bean = $missingBean;
    expectException(SugarApiExceptionNotFound::class, fn () => $invoke(['record' => $id]));
}
$account = new TestAccount();
BeanFactory::$bean = $account;
$account->canView = false;
expectException(SugarApiExceptionNotAuthorized::class, fn () => $invoke(['record' => $id]));
check($account->readFields === [], 'Denied record exposes no field values');
$account->canView = true;
foreach (array_keys($account->values) as $field) {
    if ($field === 'description') {
        continue;
    }
    $account->denied = [$field];
    expectException(SugarApiExceptionNotAuthorized::class, fn () => $invoke(['record' => $id]));
    check($account->readFields === [], 'Every field denial stops before reading values');
}
$account->denied = [];
$result = $invoke(['record' => $id]);
check($result === [
    'schema_version' => 1,
    'account' => ['id' => $id, 'name' => 'Example Account'],
    'contact' => ['phone' => '+44 20 7946 0123', 'website' => 'https://example.com'],
    'billing' => ['city' => 'London', 'country' => 'United Kingdom'],
    'handoff' => ['ready' => true, 'missing_fields' => []],
], 'Exact allowlisted contract, normalized values, no unexpected bean fields');
foreach (BeanFactory::$calls as $call) {
    check($call === ['Accounts', $id], 'No module injection or security bypass options');
}
foreach (['name', 'phone_office', 'billing_address_city', 'billing_address_country'] as $field) {
    $saved = $account->values[$field];
    $account->values[$field] = '   ';
    $result = $invoke(['record' => $id]);
    check($result['handoff'] === ['ready' => false, 'missing_fields' => [$field]], 'Presence rule: ' . $field);
    $account->values[$field] = $saved;
}
$account->values['website'] = null;
$result = $invoke(['record' => $id]);
check($result['contact']['website'] === '' && $result['handoff']['ready'], 'Website optional; null normalized');
$account->values['phone_office'] = '0';
check($invoke(['record' => $id])['handoff']['ready'], 'Presence check does not confuse zero with blank');
echo "PASS: route, input, retrieval, record/field denial, response contract, handoff rules.\n";
