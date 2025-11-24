<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;

class LanguageTest extends TestCase
{
    /**
     * 正常系：引数にとった言語以外が返却されること.
     *
     * @return void
     */
    public function testAllExcept(): void
    {
        $allExcept = Language::allExcept(Language::ENGLISH);
        $this->assertSame([Language::JAPANESE, Language::KOREAN], $allExcept);

        $allExcept = Language::allExcept(Language::KOREAN);
        $this->assertSame([Language::JAPANESE, Language::ENGLISH], $allExcept);

        $allExcept = Language::allExcept(Language::JAPANESE);
        $this->assertSame([Language::KOREAN, Language::ENGLISH], $allExcept);
    }
}
