<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail;

use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailInput;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendPasskeyRecoveryEmailInputTest extends TestCase
{
    public function testItExposesConstructorValues(): void
    {
        $email = new Email('user@example.com');
        $input = new SendPasskeyRecoveryEmailInput($email, Language::JAPANESE);

        $this->assertSame($email, $input->email());
        $this->assertSame(Language::JAPANESE, $input->language());
    }
}
