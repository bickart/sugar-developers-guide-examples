<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

// Start the links for this section, then append the section below.
$admin_option_defs = [];

$admin_option_defs['Administration']['amaiza_Substack_Launcher'] = [
    // Legacy icon name; check icon appearance in the target Sugar release.
    'Administration',

    // Link title and description label keys.
    'LBL_AMAIZA_SUBSTACK_LAUNCHER_TITLE',
    'LBL_AMAIZA_SUBSTACK_LAUNCHER_DESC',

    // External website. This does not publish content or sign the user in.
    'https://substack.com',
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
