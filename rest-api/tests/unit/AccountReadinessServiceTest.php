<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Sugarcrm\Sugarcrm\custom\modules\Accounts\Services\AccountReadinessService;

require_once dirname(__DIR__, 2) . '/custom/modules/Accounts/Services/AccountReadinessService.php';

final class AccountReadinessServiceTest extends TestCase
{
    private const RECORD = [
        'name' => 'Example Account',
        'phone_office' => '+44 20 7946 0123',
        'website' => 'https://example.com',
        'billing_address_city' => 'London',
        'billing_address_country' => 'United Kingdom',
    ];

    public function testProvidesAllFieldNamesForTheApi(): void
    {
        self::assertSame(
            ['name', 'phone_office', 'website', 'billing_address_city', 'billing_address_country'],
            (new AccountReadinessService())->getFieldNames()
        );
    }

    public function testCompleteAccountIsReady(): void
    {
        self::assertSame(
            ['ready' => true, 'missing_fields' => []],
            (new AccountReadinessService())->evaluate(self::RECORD)
        );
    }

    public function testEachRequiredFieldMustHaveAValue(): void
    {
        $service = new AccountReadinessService();
        foreach (['name', 'phone_office', 'billing_address_city', 'billing_address_country'] as $field) {
            foreach (['', '   ', null] as $blank) {
                $record = self::RECORD;
                $record[$field] = $blank;
                self::assertSame(['ready' => false, 'missing_fields' => [$field]], $service->evaluate($record));
            }
        }
    }

    public function testOmittedRequiredFieldMeansUnknown(): void
    {
        foreach (['name', 'phone_office', 'billing_address_city', 'billing_address_country'] as $field) {
            $record = self::RECORD;
            unset($record[$field]);
            self::assertSame(
                ['ready' => null, 'missing_fields' => null],
                (new AccountReadinessService())->evaluate($record)
            );
        }
    }

    public function testWebsiteIsOptionalEvenWhenOmitted(): void
    {
        $record = self::RECORD;
        unset($record['website']);
        $service = new AccountReadinessService();
        self::assertSame(['ready' => true, 'missing_fields' => []], $service->evaluate($record));
        $record['website'] = '';
        self::assertSame(['ready' => true, 'missing_fields' => []], $service->evaluate($record));
    }

    public function testMissingFieldsKeepTheirDefinedOrder(): void
    {
        $record = self::RECORD;
        $record['name'] = '';
        $record['phone_office'] = '';
        self::assertSame(
            ['ready' => false, 'missing_fields' => ['name', 'phone_office']],
            (new AccountReadinessService())->evaluate($record)
        );
    }

    public function testUnknownRequiredValuePreventsAPartialDecision(): void
    {
        $record = self::RECORD;
        $record['name'] = '';
        unset($record['billing_address_country']);
        self::assertSame(
            ['ready' => null, 'missing_fields' => null],
            (new AccountReadinessService())->evaluate($record)
        );
    }

    public function testZeroIsNotTreatedAsBlank(): void
    {
        $record = self::RECORD;
        $record['phone_office'] = '0';
        self::assertTrue((new AccountReadinessService())->evaluate($record)['ready']);
    }

    public function testEvaluationDoesNotChangeTheInput(): void
    {
        $record = self::RECORD;
        $record['name'] = ' Example Account ';
        $original = $record;
        (new AccountReadinessService())->evaluate($record);
        self::assertSame($original, $record);
    }
}
