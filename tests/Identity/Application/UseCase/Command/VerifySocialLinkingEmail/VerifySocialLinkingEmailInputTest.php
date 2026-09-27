<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInput;
use Source\Identity\Domain\ValueObject\AuthCode;
use Tests\TestCase;

class VerifySocialLinkingEmailInputTest extends TestCase
{
    public function testInput(): void
    {
        $value = new AuthCode('123456');
        $this->assertSame($value, (new VerifySocialLinkingEmailInput($value))->code());
    }
}
