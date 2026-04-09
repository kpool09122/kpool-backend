<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/application',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    ->withSets([
        LaravelSetList::LARAVEL_130,
    ]);
