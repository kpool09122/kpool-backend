<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Translation;

class TranslationTest extends TestCase
{
    /**
     * 正常系：引数にとった言語以外が返却されること.
     *
     * @return void
     */
    public function testAllExcept(): void
    {
        $allExcept = Translation::allExcept(Translation::ENGLISH);
        $this->assertSame([Translation::JAPANESE, Translation::KOREAN], $allExcept);

        $allExcept = Translation::allExcept(Translation::KOREAN);
        $this->assertSame([Translation::JAPANESE, Translation::ENGLISH], $allExcept);

        $allExcept = Translation::allExcept(Translation::JAPANESE);
        $this->assertSame([Translation::KOREAN, Translation::ENGLISH], $allExcept);
    }
}
