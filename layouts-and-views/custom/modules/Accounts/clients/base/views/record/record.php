<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

$viewdefs['Accounts']['base']['view']['record']['panels'][] = [
    'name' => 'panel_custom',
    'label' => 'LBL_PANEL_CUSTOM',
    'columns' => 2,
    'fields' => [
        'industry',
        'phone_office',
    ],
];

$viewdefs['Accounts']['base']['view']['record']['buttons'][] = [
    'type' => 'rowaction',
    'event' => 'button:sync_external:click',
    'name' => 'sync_external',
    'label' => 'LBL_SYNC_EXTERNAL',
    'css_class' => 'btn btn-primary',
    'showOn' => 'view',
    'acl_action' => 'edit',
];
