<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\TestFilesResultCacheMetaExtension;
use PHPUnit\Framework\TestCase;

class TestFilesResultCacheMetaExtensionTest extends TestCase
{
    public function testNestedTestFileAdditionAndDeletionChangeTheHashWhileContentChangesDoNot(): void
    {
        $testsDirectory = sys_get_temp_dir() . '/kpool-test-files-' . bin2hex(random_bytes(8));
        mkdir($testsDirectory . '/Nested', recursive: true);
        $testFile = $testsDirectory . '/Nested/ExampleTest.php';
        $extension = new TestFilesResultCacheMetaExtension($testsDirectory);

        try {
            $emptyHash = $extension->getHash();
            file_put_contents($testFile, '<?php');
            $withTestHash = $extension->getHash();
            $this->assertNotSame($emptyHash, $withTestHash);

            file_put_contents($testFile, '<?php // modified test contents');
            $this->assertSame($withTestHash, $extension->getHash());

            unlink($testFile);
            $this->assertSame($emptyHash, $extension->getHash());
        } finally {
            if (is_file($testFile)) {
                unlink($testFile);
            }
            rmdir($testsDirectory . '/Nested');
            rmdir($testsDirectory);
        }
    }

    public function testNonTestFilesAndTheirContentsDoNotChangeTheHash(): void
    {
        $testsDirectory = sys_get_temp_dir() . '/kpool-test-files-' . bin2hex(random_bytes(8));
        mkdir($testsDirectory);
        $helperFile = $testsDirectory . '/Helper.php';
        $extension = new TestFilesResultCacheMetaExtension($testsDirectory);

        try {
            $emptyHash = $extension->getHash();
            file_put_contents($helperFile, '<?php');
            $this->assertSame($emptyHash, $extension->getHash());

            file_put_contents($helperFile, '<?php // modified helper contents');
            $this->assertSame($emptyHash, $extension->getHash());
        } finally {
            unlink($helperFile);
            rmdir($testsDirectory);
        }
    }
}
