<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Domain\ValueObject\PasskeyRegistrationContext;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;

class PasskeyRegistrationContextTest extends TestCase
{
    public function testItRetainsTheRegistrationContext(): void
    {
        $email = new Email('passkey@example.com');
        $signupSession = new SignupSession(
            AccountType::INDIVIDUAL,
            new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH)),
            '/mypage',
        );

        $context = new PasskeyRegistrationContext($email, $signupSession);

        $this->assertSame($email, $context->email());
        $this->assertSame($signupSession, $context->signupSession());
    }
}
