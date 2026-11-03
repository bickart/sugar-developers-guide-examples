<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

// Read-only checks against licensed Sugar source. No database is used.
$sugarRoot = realpath($argv[1] ?? '');
$examples = __DIR__;
if ($sugarRoot === false || !is_file($sugarRoot . '/sugar_version.php')) {
    fwrite(STDERR, "Usage: php extension-framework/verify-sugar-source.php /path/to/Sugar\n");
    exit(1);
}

set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    exit(1);
});

define('sugarEntry', true);
chdir($sugarRoot);
require 'sugar_version.php';
require 'include/api/SugarApi.php';
require 'modules/Administration/clients/base/api/AdministrationApi.php';
require 'modules/DynamicFields/templates/Fields/TemplateField.php';
require 'modules/DynamicFields/templates/Fields/TemplateText.php';
require 'modules/DynamicFields/templates/Fields/TemplateEnum.php';
require 'include/utils/LogicHook.php';

function verifySource(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS: ' . $message . "\n";
}

verifySource(str_starts_with($sugar_version, '26.1.'), 'Source version is Sugar ' . $sugar_version);
require $examples . '/package/contact-priority/manifest.php';
require $examples . '/package/contact-priority/Extension/application/Ext/Language/en_us.contact_priority.php';
$field = new TemplateEnum();
$field->populateFromRow($installdefs['custom_fields'][0]);
$definition = $field->get_field_def();
verifySource($definition['required'] === false, 'Generated field is optional (boolean false)');
verifySource($definition['massupdate'] === false, 'Mass update is disabled');
verifySource($definition['source'] === 'custom_fields', 'Field uses custom_fields metadata');
verifySource($definition['options'] === 'contact_priority_list', 'Dropdown key survives field conversion');
verifySource(array_key_exists($field->default_value, $app_list_strings[$definition['options']]), 'Dropdown includes the default choice');

require $examples . '/custom/Extension/modules/Administration/Ext/Administration/amaiza_Substack.php';
require $examples . '/custom/Extension/modules/Administration/Ext/Language/en_us.amaiza_Substack.php';
$parser = new class () extends AdministrationApi {
    public array $definitions = [];

    public function getAdminPanelLegacyDefs(): array
    {
        return $this->definitions;
    }

    protected function getImageFromTheme($imageName)
    {
        // Theme assets are unavailable without a configured instance.
        return false;
    }
};
$parser->definitions = $admin_group_header;
$parsed = $parser->getParsedAdminPanelDefsFromLegacyDefs();
$option = $parsed[0]['options'][0];
verifySource($option['link'] === 'https://substack.com', 'Administration parser keeps the external URL');
verifySource(!empty($option['icon']), 'Administration parser supplies a fallback icon');
foreach ([$parsed[0]['label'], $parsed[0]['description'], $option['label'], $option['description']] as $label) {
    verifySource(isset($mod_strings[$label]), 'Administration label exists: ' . $label);
}

$scanner = new LogicHook();
$scanner->scanHooksDir($examples . '/custom/Extension/modules/SchedulersJobs/Ext/LogicHooks');
$hook = $scanner->getHooksList()['job_failure'][0];
require $examples . '/custom/modules/SchedulersJobs/LogicHooks/SchedulersJobsLogicHooks.php';
$GLOBALS['log'] = new class () {
    public array $warnings = [];

    public function warning(string $message): void
    {
        $this->warnings[] = $message;
    }
};
$handler = new ($hook[3])();
$handler->{$hook[4]}((object) ['id' => 'synthetic-test', 'name' => 'Guide Test'], 'job_failure', []);
verifySource($GLOBALS['log']->warnings === ['Scheduler job "Guide Test" reached its final failure.'], 'Sugar hook scanner resolves the example logging method');

echo "Source checks only: database installation, repair, job-worker execution, and browser testing remain separate.\n";
