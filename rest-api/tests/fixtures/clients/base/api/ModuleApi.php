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
