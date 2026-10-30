<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SugarDevelopersGuide\Examples\AccountSummaryClient;
use Sugarcrm\Sugarcrm\custom\modules\Accounts\Services\AccountReadinessService;

require_once dirname(__DIR__, 2) . '/custom/modules/Accounts/Services/AccountReadinessService.php';
require_once dirname(__DIR__, 2) . '/examples/account_summary.php';

/** Opt-in, read-only tests against an installed Sugar sandbox. */
final class AccountSummaryEndpointTest extends TestCase
{
    private string $base;
    private string $record;
    private string $token;

    protected function setUp(): void
    {
        $this->base = getenv('SDG_TEST_BASE') ?: '';
        $this->record = getenv('SDG_TEST_ACCOUNT_ID') ?: '';
        $this->token = getenv('SDG_TEST_ACCESS_TOKEN') ?: '';
        if ($this->base === '' || $this->record === '' || $this->token === '') {
            self::markTestSkipped('Set SDG_TEST_BASE, SDG_TEST_ACCOUNT_ID, and SDG_TEST_ACCESS_TOKEN for a Sugar sandbox.');
        }
        if (!extension_loaded('curl')) {
            self::markTestSkipped('The PHP cURL extension is required for live integration tests.');
        }
    }

    public function testSummaryMatchesTheAllowedBuiltInRecordResponse(): void
    {
        $client = new AccountSummaryClient(null, fn () => $this->token);
        $data = $client->fetch($this->base, $this->record, null, true);
        $actual = $client->fetch($this->base, $this->record);
        $service = new AccountReadinessService();
        $values = [];
        foreach ($service->getFieldNames() as $field) {
            $values[$field] = array_key_exists($field, $data) ? trim((string) ($data[$field] ?? '')) : null;
        }
        self::assertSame([
            'schema_version' => 1,
            'account' => ['id' => $data['id'], 'name' => $values['name']],
            'contact' => ['phone' => $values['phone_office'], 'website' => $values['website']],
            'billing' => ['city' => $values['billing_address_city'], 'country' => $values['billing_address_country']],
            'handoff' => $service->evaluate($data),
        ], $actual);
    }

    public function testAnInvalidTokenIsRejected(): void
    {
        $client = new AccountSummaryClient(null, fn () => 'sdg-intentionally-invalid-token');
        try {
            $client->fetch($this->base, $this->record);
            self::fail('Sugar accepted an invalid access token.');
        } catch (RuntimeException $error) {
            self::assertMatchesRegularExpression('/^HTTP (401|403):/', $error->getMessage());
        }
    }
}
