<?php

declare(strict_types=1);

namespace Tests\Resources\Lang;

use Tests\TestCase;

class ErrorMessagesTest extends TestCase
{
    public function testAllLanguagesHaveTheSameErrorMessageKeys(): void
    {
        $languageDirectory = dirname(__DIR__, 3) . '/resources/lang';
        $expected = require $languageDirectory . '/en/errors.php';
        self::assertIsArray($expected);
        $expectedKeys = array_keys($expected);
        sort($expectedKeys);

        foreach (glob($languageDirectory . '/*/errors.php') ?: [] as $file) {
            $actual = require $file;
            self::assertIsArray($actual);
            $actualKeys = array_keys($actual);
            sort($actualKeys);

            $this->assertSame(
                $expectedKeys,
                $actualKeys,
                sprintf('Error message keys are incomplete in %s.', $file),
            );
        }
    }
}
