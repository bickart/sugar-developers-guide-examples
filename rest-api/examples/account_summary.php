<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace SugarDevelopersGuide\Examples;

use Closure;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

/** Standalone PHP 8.2+ client. Run outside Sugar; requires the PHP cURL extension. */
final class AccountSummaryClient
{
    private const FIELDS = 'name,phone_office,website,billing_address_city,billing_address_country';
    private Closure $send;
    private Closure $readSecret;

    /** Optional callbacks let tests inspect requests without contacting a server. */
    public function __construct(?Closure $send = null, ?Closure $readSecret = null)
    {
        $this->send = $send ?? self::sendHttp(...);
        $this->readSecret = $readSecret ?? self::promptSecret(...);
    }

    public function fetch(string $base, string $record, ?string $username = null, bool $standard = false): array
    {
        $parts = parse_url($base);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || filter_var($base, FILTER_VALIDATE_URL) === false
            || !preg_match('~/rest/v\d+(?:_\d+)?/?$~D', $parts['path'] ?? '')
        ) {
            throw new InvalidArgumentException('Use an HTTPS API base such as https://crm.example.com/rest/v11');
        }
        $base = rtrim($base, '/');
        if ($username !== null) {
            if (trim($username) === '') {
                throw new InvalidArgumentException('--username cannot be blank');
            }
            $password = ($this->readSecret)('Sugar password: ');
            if ($password === '') {
                throw new InvalidArgumentException('A password is required');
            }
            $auth = ($this->send)($base . '/oauth2/token', 'POST', [
                'Accept: application/json',
                'Content-Type: application/json',
            ], json_encode([
                'grant_type' => 'password',
                'client_id' => 'sugar',
                'client_secret' => '',
                'platform' => 'sdg_examples',
                'username' => $username,
                'password' => $password,
            ], JSON_THROW_ON_ERROR));
            $token = $auth['access_token'] ?? null;
            unset($password, $auth);
        } else {
            $token = ($this->readSecret)('OAuth access token: ');
        }
        if (!is_string($token) || $token === '' || preg_match('/[\x00-\x20\x7f]/', $token)) {
            throw new RuntimeException('A valid access token is required');
        }
        $recordPath = rawurlencode($record);
        $path = $standard
            ? '/Accounts/' . $recordPath . '?' . http_build_query(['fields' => self::FIELDS], '', '&', PHP_QUERY_RFC3986)
            : '/SDG/Accounts/' . $recordPath . '/summary';

        return ($this->send)($base . $path, 'GET', [
            'Accept: application/json',
            'OAuth-Token: ' . $token,
        ], null);
    }

    /** HTTP and JSON failures never include a response body or credentials. */
    public static function decodeResponse(string $body, int $status): array
    {
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("HTTP {$status}: request failed. Check API help and permissions.");
        }
        try {
            $value = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('The server did not return valid JSON');
        }
        if (!is_array($value) || !str_starts_with(ltrim($body), '{')) {
            throw new RuntimeException('The server did not return a JSON object');
        }
        return $value;
    }

    private static function sendHttp(string $url, string $method, array $headers, ?string $body): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('Enable the PHP cURL extension before running the client');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Unable to start the HTTP request');
        }
        try {
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => $method,
            ];
            if ($body !== null) {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
            if (!curl_setopt_array($handle, $options)) {
                throw new RuntimeException('Unable to set HTTP request options');
            }
            $response = curl_exec($handle);
            if ($response === false) {
                throw new RuntimeException('Unable to connect to Sugar. Check the URL, network, and TLS certificate.');
            }
            return self::decodeResponse($response, (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE));
        } finally {
            curl_close($handle);
        }
    }

    /** Hidden terminal input on macOS/Linux; redirected standard input works on any OS. */
    private static function promptSecret(string $prompt): string
    {
        $state = null;
        if (stream_isatty(STDIN)) {
            if (PHP_OS_FAMILY === 'Windows' || !function_exists('exec')) {
                throw new RuntimeException('Hidden input needs a macOS/Linux terminal with stty. Supply the secret through standard input instead.');
            }
            exec('stty -g 2>/dev/null', $lines, $status);
            $state = $lines[0] ?? '';
            if ($status !== 0 || $state === '') {
                throw new RuntimeException('Unable to hide terminal input. Supply the secret through standard input instead.');
            }
            exec('stty -echo 2>/dev/null', $unused, $status);
            if ($status !== 0) {
                throw new RuntimeException('Unable to hide terminal input');
            }
        }
        fwrite(STDERR, $prompt);
        try {
            $line = fgets(STDIN);
            if ($line === false) {
                throw new RuntimeException('No secret was supplied on standard input');
            }
            return rtrim($line, "\r\n");
        } finally {
            if ($state !== null) {
                exec('stty ' . escapeshellarg($state) . ' 2>/dev/null');
                fwrite(STDERR, "\n");
            }
        }
    }
}

// Including this file in tests loads the class without running the command.
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        if (PHP_VERSION_ID < 80200) {
            throw new RuntimeException('This client requires PHP 8.2 or later');
        }
        $options = getopt('', ['base:', 'record:', 'username:', 'standard', 'help']);
        if (isset($options['help'])) {
            fwrite(STDOUT, "Usage: php rest-api/examples/account_summary.php --base HTTPS-API-BASE --record ACCOUNT-ID [--username USER] [--standard]\n");
            exit(0);
        }
        foreach (['base', 'record'] as $required) {
            if (!isset($options[$required]) || !is_string($options[$required])) {
                throw new InvalidArgumentException("--{$required} is required once. Use --help for usage.");
            }
        }
        if (isset($options['username']) && !is_string($options['username'])) {
            throw new InvalidArgumentException('--username must be supplied once');
        }
        if (!extension_loaded('curl')) {
            throw new RuntimeException('Enable the PHP cURL extension before running the client');
        }
        $result = (new AccountSummaryClient())->fetch(
            $options['base'],
            $options['record'],
            $options['username'] ?? null,
            isset($options['standard'])
        );
        fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
    } catch (Throwable $error) {
        fwrite(STDERR, 'Request failed: ' . $error->getMessage() . "\n");
        exit(1);
    }
}
