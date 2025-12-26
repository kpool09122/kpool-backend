<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\Login;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\Login\LoginInput;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Shared\Domain\ValueObject\Email;

class LoginInputTest extends TestCase
{
    public function test__construct(): void
    {
        $email = new Email('user@example.com');
        $password = new PlainPassword('PlainPass1!');

        $input = new LoginInput($email, $password);

        $this->assertSame($email, $input->email());
        $this->assertSame($password, $input->password());
    }
}
