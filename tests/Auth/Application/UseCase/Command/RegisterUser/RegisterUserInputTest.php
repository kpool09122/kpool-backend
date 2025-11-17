<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\RegisterUser;

use PHPUnit\Framework\TestCase;
use Source\Auth\Application\UseCase\Command\RegisterUser\RegisterUserInput;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Translation;

class RegisterUserInputTest extends TestCase
{
    public function test__construct(): void
    {
        $userName = new UserName('userName');
        $email = new Email('user@example.com');
        $translation = Translation::KOREAN;
        $password = new PlainPassword('PlainPass1!');
        $confirmedPassword = new PlainPassword('PlainPass1!');
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';


        $input = new RegisterUserInput(
            $userName,
            $email,
            $translation,
            $password,
            $confirmedPassword,
            $base64EncodedImage,
        );

        $this->assertSame($userName, $input->userName());
        $this->assertSame($email, $input->email());
        $this->assertSame($translation, $input->translation());
        $this->assertSame($password, $input->password());
        $this->assertSame($confirmedPassword, $input->confirmedPassword());
        $this->assertSame($base64EncodedImage, $input->base64EncodedImage());
    }
}
