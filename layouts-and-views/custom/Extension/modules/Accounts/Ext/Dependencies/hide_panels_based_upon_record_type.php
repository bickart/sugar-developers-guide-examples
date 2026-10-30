<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

$dependencies['Accounts']['HidePanelsBasedUponRecordType'] = [
    'hooks' => ['edit', 'view'],
    'trigger' => 'true',
    'triggerFields' => ['record_type'],
    'onload' => true,
    'actions' => [
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_DEALCOMPANY',
                'value' => 'equal($record_type, "012300000008hws")',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_INPUTS',
                'value' => 'equal($record_type, "012300000008hws")',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_SOURCESCRUB',
                'value' => 'equal($record_type, "012300000008hws")',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_INTERMEDIARY',
                'value' => 'equal($record_type, "012300000007zin")',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_INVESTOR',
                'value' => 'equal($record_type, "012300000008hwr")',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_RECRUITING',
                'value' => 'equal($record_type, "012300000008hwt")',
            ],
        ],
    ],
    'notActions' => [
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_DEALCOMPANY',
                'value' => 'not(equal($record_type, "012300000008hws"))',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_INPUTS',
                'value' => 'not(equal($record_type, "012300000008hws"))',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_SOURCESCRUB',
                'value' => 'not(equal($record_type, "012300000008hws"))',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_INTERMEDIARY',
                'value' => 'not(equal($record_type, "012300000007zin"))',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_INVESTOR',
                'value' => 'not(equal($record_type, "012300000008hwr"))',
            ],
        ],
        [
            'name' => 'SetPanelVisibility',
            'params' => [
                'target' => 'LBL_RECORDVIEW_RECRUITING',
                'value' => 'not(equal($record_type, "012300000008hwt"))',
            ],
        ],
    ],
];
