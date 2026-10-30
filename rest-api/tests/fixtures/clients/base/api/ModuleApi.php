<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

// A test double, not an implementation of Sugar's loader or permissions.
class ModuleApi
{
    public static array $calls = [];
    public static array $response = [];
    public static ?Throwable $error = null;

    public function retrieveRecord($api, array $args)
    {
        self::$calls[] = [$api, $args];
        if (self::$error !== null) {
            throw self::$error;
        }
        return self::$response;
    }
}
