<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail\VerifyPasskeyRecoveryEmailInput;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;

class VerifyPasskeyRecoveryEmailInputTest extends TestCase
{
    public function testItExposesConstructorValues(): void
    {
        $email = new Email('user@example.com');
        $code = new AuthCode('123456');
        $input = new VerifyPasskeyRecoveryEmailInput($email, $code);

        $this->assertSame($email, $input->email());
        $this->assertSame($code, $input->code());
    }
}
