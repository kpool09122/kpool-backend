<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\VerifyEmail;

use PHPUnit\Framework\TestCase;
use Source\Auth\Application\UseCase\Command\VerifyEmail\VerifyEmailInput;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

class VerifyEmailInputTest extends TestCase
{
    public function test__construct(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');

        $input = new VerifyEmailInput($email, $authCode);

        $this->assertSame($email, $input->email());
        $this->assertSame($authCode, $input->authCode());
    }
}
