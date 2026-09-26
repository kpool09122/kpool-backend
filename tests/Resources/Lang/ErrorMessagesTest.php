<?php

declare(strict_types=1);

namespace Tests\Resources\Lang;

use Tests\TestCase;

class ErrorMessagesTest extends TestCase
{
    public function testAllLanguagesHaveTheSameErrorMessageKeys(): void
    {
        $languageDirectory = dirname(__DIR__, 3) . '/resources/lang';
        $expectedKeys = array_keys(require $languageDirectory . '/en/errors.php');
        sort($expectedKeys);

        foreach (glob($languageDirectory . '/*/errors.php') ?: [] as $file) {
            $actualKeys = array_keys(require $file);
            sort($actualKeys);

            $this->assertSame(
                $expectedKeys,
                $actualKeys,
                sprintf('Error message keys are incomplete in %s.', $file),
            );
        }
    }
}
