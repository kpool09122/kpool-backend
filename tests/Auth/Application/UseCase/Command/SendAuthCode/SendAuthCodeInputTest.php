<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\SendAuthCode;

use PHPUnit\Framework\TestCase;
use Source\Auth\Application\UseCase\Command\SendAuthCode\SendAuthCodeInput;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

class SendAuthCodeInputTest extends TestCase
{
    public function test__construct(): void
    {
        $email = new Email('user@example.com');
        $language = Language::KOREAN;

        $input = new SendAuthCodeInput($email, $language);

        $this->assertSame($email, $input->email());
        $this->assertSame($language, $input->language());
    }
}
