#!/usr/bin/env php
<?php

/*
 * Copyright (c) 2026 Sugar Developers Guide
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

$topic = $argv[1] ?? null;
$version = $argv[2] ?? null;

if ($topic === null || $version === null || !preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    exit("Usage: php bin/pack.php <topic> <major.minor.patch>\n");
}

if (basename($topic) !== $topic) {
    exit("Topic must be a top-level directory name.\n");
}

$projectRoot = dirname(__DIR__);
$topicRoot = $projectRoot . '/' . $topic;
$customRoot = $topicRoot . '/custom';

if (!is_dir($topicRoot) || !is_dir($customRoot)) {
    exit("Unknown package topic: {$topic}\n");
}

$topicConfig = getTopicConfiguration($topic);
$buildDirectory = $topicRoot . '/builds';
$archivePath = sprintf(
    '%s/%s_%s.zip',
    $buildDirectory,
    strtolower($topicConfig['package_id']),
    $version
);

if (file_exists($archivePath)) {
    exit("Build already exists: {$archivePath}\n");
}

if (!is_dir($buildDirectory) && !mkdir($buildDirectory, 0755, true) && !is_dir($buildDirectory)) {
    exit("Unable to create build directory: {$buildDirectory}\n");
}

[$manifest, $installdefs] = loadTopicMetadata($topicRoot, $topicConfig, $version);
$zip = new ZipArchive();

if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    exit("Unable to create archive: {$archivePath}\n");
}

try {
    if ($topicConfig['metadata_directory'] !== null) {
        addDirectory(
            $zip,
            $topicRoot . '/' . $topicConfig['metadata_directory'] . '/Extension',
            'Extension'
        );
    }

    addCustomSources($zip, $customRoot, $installdefs);

    if (!$zip->addFile($projectRoot . '/LICENSE', 'LICENSE.txt')) {
        throw new RuntimeException('Unable to add LICENSE.txt to the archive.');
    }

    $manifestContent = "<?php\n"
        . '$manifest = ' . var_export($manifest, true) . ";\n"
        . '$installdefs = ' . var_export($installdefs, true) . ";\n";

    if (!$zip->addFromString('manifest.php', $manifestContent)) {
        throw new RuntimeException('Unable to add manifest.php to the archive.');
    }
} finally {
    $zip->close();
}

echo "Created {$archivePath}\n";

/**
 * @return array{package_id: string, package_label: string, metadata_directory: string|null}
 */
function getTopicConfiguration(string $topic): array
{
    $configurations = [
        'rest-api' => [
            'package_id' => 'SugarDevelopersGuideAccountsRestApiExample',
            'package_label' => 'Accounts RestAPI Example',
            'metadata_directory' => null,
        ],
        'extension-framework' => [
            'package_id' => 'SugarDevelopersGuideExtensionFrameworkExamples',
            'package_label' => 'Sugar Developers Guide | Extension Framework Examples',
            'metadata_directory' => 'package/contact-priority',
        ],
    ];

    if (isset($configurations[$topic])) {
        return $configurations[$topic];
    }

    $label = ucwords(str_replace('-', ' ', $topic));

    return [
        'package_id' => 'SugarDevelopersGuide' . str_replace(' ', '', $label) . 'Examples',
        'package_label' => 'Sugar Developers Guide | ' . $label . ' Examples',
        'metadata_directory' => null,
    ];
}

/**
 * @param array{package_id: string, package_label: string, metadata_directory: string|null} $topicConfig
 * @return array{0: array<string, mixed>, 1: array<string, mixed>}
 */
function loadTopicMetadata(string $topicRoot, array $topicConfig, string $version): array
{
    $manifest = [];
    $installdefs = [];

    if ($topicConfig['metadata_directory'] !== null) {
        require $topicRoot . '/' . $topicConfig['metadata_directory'] . '/manifest.php';
    }

    $manifest = array_replace($manifest, [
        'id' => $topicConfig['package_id'],
        'name' => $topicConfig['package_label'],
        'description' => 'Installable examples for Sugar customizations.',
        'version' => $version,
        'author' => 'Sugar Developers Guide',
        'is_uninstallable' => true,
        'built_in_version' => '26.1.0',
        'published_date' => date('Y-m-d H:i:s'),
        'type' => 'module',
        'remove_tables' => false,
        'acceptable_sugar_versions' => [
            'regex_matches' => ['^26\\.(.*?)\\.(.*?)'],
        ],
        'acceptable_sugar_flavors' => ['PRO', 'ENT', 'ULT'],
    ]);

    $installdefs = array_replace($installdefs, [
        'id' => $topicConfig['package_id'],
        'copy' => [],
    ]);

    return [$manifest, $installdefs];
}

function addDirectory(ZipArchive $zip, string $sourceDirectory, string $archiveDirectory): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $relativePath = substr($file->getPathname(), strlen($sourceDirectory) + 1);

        if (!isDeployableFile($relativePath)) {
            continue;
        }

        $archivePath = $archiveDirectory . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

        if (!$zip->addFile($file->getPathname(), $archivePath)) {
            throw new RuntimeException("Unable to add {$archivePath} to the archive.");
        }
    }
}

/**
 * @param array<string, mixed> $installdefs
 */
function addCustomSources(ZipArchive $zip, string $customDirectory, array &$installdefs): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($customDirectory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $relativePath = substr($file->getPathname(), strlen($customDirectory) + 1);

        if (!isDeployableFile($relativePath)) {
            continue;
        }

        $archivePath = 'custom/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

        if (!$zip->addFile($file->getPathname(), $archivePath)) {
            throw new RuntimeException("Unable to add {$archivePath} to the archive.");
        }

        $languageDefinition = getLanguageDefinition($archivePath);

        if ($languageDefinition !== null) {
            $installdefs['language'][] = $languageDefinition;
            continue;
        }

        $installdefs['copy'][] = [
            'from' => '<basepath>/' . $archivePath,
            'to' => $archivePath,
        ];
    }
}

/**
 * @return array{from: string, to_module: string, language: string}|null
 */
function getLanguageDefinition(string $archivePath): ?array
{
    if (!preg_match(
        '#^custom/Extension/(?:modules/([^/]+)/|application/)Ext/Language/([a-z]{2}_[A-Z]{2})[^/]*\.php$#',
        $archivePath,
        $matches
    )) {
        return null;
    }

    return [
        'from' => '<basepath>/' . $archivePath,
        'to_module' => ($matches[1] ?? '') !== '' ? $matches[1] : 'application',
        'language' => $matches[2],
    ];
}

function isDeployableFile(string $path): bool
{
    $excludedNames = ['.DS_Store', '.gitkeep', 'orderMapping.php'];

    return !in_array(basename($path), $excludedNames, true);
}
