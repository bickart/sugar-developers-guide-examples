<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

// Intentionally incorrect: this does not create a Studio-compliant custom field.
// Use Studio or a Module Loadable Package custom_fields definition instead.
$dictionary['Contacts']['fields']['custom_priority_c'] = [
    'name' => 'custom_priority_c',
    'type' => 'enum',
    'options' => 'contact_priority_list',
];
