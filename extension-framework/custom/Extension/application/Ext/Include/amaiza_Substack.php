<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

// Registration alone does not create a module. Its class must already exist.
if (is_file('modules/amaiza_Substack/amaiza_Substack.php')) {
    $beanList['amaiza_Substack'] = 'amaiza_Substack';
    $beanFiles['amaiza_Substack'] = 'modules/amaiza_Substack/amaiza_Substack.php';
    $moduleList[] = 'amaiza_Substack';
}
