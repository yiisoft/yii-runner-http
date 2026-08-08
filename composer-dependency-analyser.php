<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // These are only referenced in @throws PHPDoc annotations, not in actual code, so the analyser doesn't
    // detect the usage in src/; they are genuinely required for consumers to configure the DI container and
    // config layer this package's runner wires together (also used directly in tests).
    ->ignoreErrorsOnPackages(
        ['yiisoft/config', 'yiisoft/definitions', 'yiisoft/di'],
        [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV],
    );
