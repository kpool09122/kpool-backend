<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use FilesystemIterator;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class TestFilesResultCacheMetaExtension implements ResultCacheMetaExtension
{
    public function __construct(private string $testsDirectory)
    {
    }

    public function getKey(): string
    {
        return 'kpool.dedicatedTestFiles';
    }

    public function getHash(): string
    {
        $testPaths = [];
        if (is_dir($this->testsDirectory)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->testsDirectory, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                    $testPaths[] = str_replace('\\', '/', $file->getPathname());
                }
            }
        }
        sort($testPaths, SORT_STRING);

        return hash('sha256', implode("\n", $testPaths));
    }
}
