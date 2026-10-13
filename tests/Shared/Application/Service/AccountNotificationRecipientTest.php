<?php

declare(strict_types=1);

namespace Tests\Shared\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

class AccountNotificationRecipientTest extends TestCase
{
    public function testPreservesEmailAndLanguage(): void
    {
        $email = new Email('recipient@example.com');
        $recipient = new AccountNotificationRecipient($email, Language::JAPANESE);
        $this->assertSame($email, $recipient->email());
        $this->assertSame(Language::JAPANESE, $recipient->language());
    }
}
