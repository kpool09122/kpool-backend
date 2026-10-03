<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailInput;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendSocialLinkingEmailInputTest extends TestCase
{
    public function testInput(): void
    {
        $value = Language::JAPANESE;
        $this->assertSame($value, (new SendSocialLinkingEmailInput($value))->language());
    }
}
