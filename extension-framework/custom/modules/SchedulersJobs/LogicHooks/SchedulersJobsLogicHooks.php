<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

namespace Sugarcrm\Sugarcrm\custom\modules\SchedulersJobs\LogicHooks;

class SchedulersJobsLogicHooks
{
    public function sendJobFailureNotification($bean, $event, $arguments): void
    {
        $jobName = $bean->name ?? $bean->id;

        $GLOBALS['log']->warning(
            sprintf('Scheduler job "%s" reached its final failure.', $jobName)
        );
    }
}
