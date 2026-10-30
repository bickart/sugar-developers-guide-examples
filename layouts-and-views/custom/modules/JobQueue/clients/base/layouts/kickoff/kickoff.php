<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

$viewdefs['JobQueue']['base']['layout']['kickoff'] = [
    'components' => [
        [
            'layout' => [
                'type' => 'default',
                'name' => 'sidebar',
                'components' => [
                    [
                        'layout' => [
                            'type' => 'base',
                            'name' => 'main-pane',
                            'css_class' => 'main-pane span8',
                            'components' => [
                                ['view' => 'kickoff-headerpane'],
                                ['view' => 'kickoff-content'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
