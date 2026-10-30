<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

// Sugar builds each admin panel section with its own $admin_option_defs array.
$admin_option_defs = [];

$admin_option_defs['Amaiza']['amaiza_Substack_Launcher'] = [
    // Legacy BWC icon image name, without the .gif extension.
    'Administration',

    // Sidecar icon class.
    'icon' => 'sicon-process-definitions-lg',

    // Link title and description label keys.
    'LBL_AMAIZA_SUBSTACK_LAUNCHER_TITLE',
    'LBL_AMAIZA_SUBSTACK_LAUNCHER_DESC',

    // Link URL.
    'https://substack.com',

    // Optional warning flag and onclick handler.
    null,
    null,

    // Open the link in a new tab.
    '_blank',
];

$admin_group_header[] = [
    // Section header label key.
    'LBL_AMAIZA_SUBSTACK_SECTION_HEADER',

    // Optional BWC header text and help text flag.
    '',
    false,

    // Links for this section.
    $admin_option_defs,

    // Section description label key.
    'LBL_AMAIZA_SUBSTACK_SECTION_DESCRIPTION',
];
