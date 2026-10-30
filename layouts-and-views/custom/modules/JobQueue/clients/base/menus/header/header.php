<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

$viewdefs['JobQueue']['base']['menu']['header'][] = [
    'route' => '#JobQueue/layout/kickoff',
    'label' => 'LBL_KICKOFF_LINK_LABEL',
    'acl_action' => 'admin',
    'acl_module' => '',
    'icon' => 'sicon-process-definitions-lg',
];
