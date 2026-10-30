<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

use SugarDevelopersGuide\Examples\AccountSummaryClient;

require dirname(__DIR__) . '/examples/account_summary.php';

function assertClient(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function clientFailure(callable $action, string $message): void
{
    try {
        $action();
    } catch (InvalidArgumentException | RuntimeException $error) {
        assertClient(str_contains($error->getMessage(), $message), 'Unexpected error: ' . $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected failure: ' . $message);
}

$base = 'https://crm.example.com/rest/v11';
$record = '11111111-2222-3333-4444-555555555555';
$calls = [];
$prompts = [];
$send = function ($url, $method, $headers, $body) use (&$calls): array {
    $calls[] = compact('url', 'method', 'headers', 'body');
    return str_ends_with($url, '/oauth2/token')
        ? ['access_token' => 'test-token', 'refresh_token' => 'do-not-output']
        : ['schema_version' => 1];
};
$read = function ($prompt) use (&$prompts): string {
    $prompts[] = $prompt;
    return 'not-a-real-secret';
};
$client = new AccountSummaryClient($send, $read);

// Fail before asking for a secret or sending a request.
foreach (['http://example.com/rest/v11', 'https://example.com', $base . '?token=x',
    $base . '#x', 'https://user@example.com/rest/v11', 'https://u:p@example.com/rest/v11'] as $invalid) {
    clientFailure(fn () => $client->fetch($invalid, $record), 'HTTPS API base');
}
clientFailure(fn () => $client->fetch($base, $record, ''), 'cannot be blank');
assertClient($calls === [] && $prompts === [], 'Invalid input must not request secrets or network');

$result = $client->fetch($base . '/', $record, 'test-user');
assertClient($result === ['schema_version' => 1], 'Only account response is returned');
assertClient(count($calls) === 2, 'Login followed by one record request');
$login = $calls[0];
$payload = json_decode($login['body'], true, 512, JSON_THROW_ON_ERROR);
assertClient($login['url'] === $base . '/oauth2/token' && $login['method'] === 'POST', 'Login address and method');
assertClient($payload === [
    'grant_type' => 'password', 'client_id' => 'sugar', 'client_secret' => '',
    'platform' => 'sdg_examples', 'username' => 'test-user', 'password' => 'not-a-real-secret',
], 'Exact Sugar login payload');
assertClient(in_array('Content-Type: application/json', $login['headers'], true), 'JSON login content type');
assertClient(!in_array('OAuth-Token: test-token', $login['headers'], true), 'No access token on login');
assertClient($calls[1] === [
    'url' => $base . '/SDG/Accounts/' . $record . '/summary',
    'method' => 'GET',
    'headers' => ['Accept: application/json', 'OAuth-Token: test-token'],
    'body' => null,
], 'Exact authenticated summary request');
assertClient($prompts === ['Sugar password: '], 'Password flow prompts once');

$calls = [];
$prompts = [];
$client->fetch('https://example.com/sugar/rest/v11', $record, null, true);
assertClient(count($calls) === 1 && $calls[0]['method'] === 'GET', 'Existing token does not log in again');
assertClient($calls[0]['url'] === 'https://example.com/sugar/rest/v11/Accounts/' . $record . '?fields='
    . rawurlencode('name,phone_office,website,billing_address_city,billing_address_country'), 'Built-in endpoint and fields');
assertClient($calls[0]['headers'] === ['Accept: application/json', 'OAuth-Token: not-a-real-secret'], 'Existing token header');
assertClient($prompts === ['OAuth access token: '], 'Token flow prompts once');

$calls = [];
$client->fetch($base, 'record/with spaces');
assertClient($calls[0]['url'] === $base . '/SDG/Accounts/record%2Fwith%20spaces/summary', 'Encode record ID as one path value without a GUID rule');

foreach (['', "token\r\nInjected: value", 'token with space'] as $badToken) {
    $calls = [];
    $bad = new AccountSummaryClient($send, fn () => $badToken);
    clientFailure(fn () => $bad->fetch($base, $record), 'valid access token');
    assertClient($calls === [], 'Bad token cannot reach HTTP headers');
}
$calls = [];
clientFailure(fn () => (new AccountSummaryClient($send, fn () => ''))->fetch($base, $record, 'test-user'), 'password is required');
assertClient($calls === [], 'Blank password must not start a login');
$badAuth = new AccountSummaryClient(fn () => ['access_token' => ['invalid']], $read);
clientFailure(fn () => $badAuth->fetch($base, $record, 'test-user'), 'valid access token');

assertClient(AccountSummaryClient::decodeResponse('{"schema_version":1}', 200) === ['schema_version' => 1], 'Decode JSON response');
foreach ([301, 302, 401, 403, 404, 422, 500] as $status) {
    clientFailure(fn () => AccountSummaryClient::decodeResponse('secret-response-body', $status), "HTTP {$status}:");
}
clientFailure(fn () => AccountSummaryClient::decodeResponse('not-json', 200), 'valid JSON');
foreach (['[]', 'null', '"text"'] as $body) {
    clientFailure(fn () => AccountSummaryClient::decodeResponse($body, 200), 'JSON object');
}

echo "PASS: PHP client input, login, token, headers, built-in/custom paths, JSON, and HTTP errors.\n";
