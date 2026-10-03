<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInput;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;

class CreatePasskeyRegistrationOptionsInputTest extends TestCase
{
    public function test__construct(): void
    {
        $email = new Email('passkey@example.com');
        $signupSession = new SignupSession(
            AccountType::INDIVIDUAL,
            new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH)),
            '/welcome',
        );

        $input = new CreatePasskeyRegistrationOptionsInput($email, $signupSession);

        $this->assertSame($email, $input->email());
        $this->assertSame($signupSession, $input->signupSession());
    }
}
