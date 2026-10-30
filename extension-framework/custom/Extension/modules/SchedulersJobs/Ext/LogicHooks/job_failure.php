<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

$hook_array['job_failure'][] = [
    120,
    'job failed',
    null,
    'Sugarcrm\\Sugarcrm\\custom\\modules\\SchedulersJobs\\LogicHooks\\SchedulersJobsLogicHooks',
    'sendJobFailureNotification',
];
