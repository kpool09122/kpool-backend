<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\AuthCodeMail;
use Application\Mail\PasskeyRecoveryCodeMail;
use Application\Mail\SocialLinkingCodeMail;
use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AuthCodeMailTest extends TestCase
{
    public function testAllCodeEmailsDisplayTenMinutesInEveryLanguage(): void
    {
        view()->addLocation(dirname(__DIR__, 4) . '/resources/views');
        $session = new AuthCodeSession(new Email('user@example.com'), new AuthCode('123456'), new DateTimeImmutable());

        foreach (Language::cases() as $language) {
            $duration = match ($language) {
                Language::JAPANESE => '10分',
                Language::ENGLISH => '10 minutes',
                Language::KOREAN => '10분',
            };
            foreach ([
                new AuthCodeMail($language, $session),
                new PasskeyRecoveryCodeMail($language, '123456'),
                new SocialLinkingCodeMail($language, '123456'),
            ] as $mail) {
                $html = $mail->render();
                $this->assertStringContainsString($duration, $html);
                $this->assertStringContainsString('123456', $html);
            }
        }
    }
}
