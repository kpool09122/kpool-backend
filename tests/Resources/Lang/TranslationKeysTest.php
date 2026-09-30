<?php

declare(strict_types=1);

namespace Tests\Resources\Lang;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TranslationKeysTest extends TestCase
{
    #[TestWith(['errors'])]
    #[TestWith(['section_titles'])]
    public function testAllLanguagesHaveTheSameTranslationKeys(string $group): void
    {
        $languageDirectory = dirname(__DIR__, 3) . '/resources/lang';
        $expected = require $languageDirectory . '/en/' . $group . '.php';
        self::assertIsArray($expected);
        $expectedKeys = array_keys($expected);
        sort($expectedKeys);

        foreach (glob($languageDirectory . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $file = $directory . '/' . $group . '.php';
            self::assertFileExists($file);
            $actual = require $file;
            self::assertIsArray($actual);
            $actualKeys = array_keys($actual);
            sort($actualKeys);

            $this->assertSame(
                $expectedKeys,
                $actualKeys,
                sprintf('Translation keys differ from English in %s.', $file),
            );
        }
    }
}
