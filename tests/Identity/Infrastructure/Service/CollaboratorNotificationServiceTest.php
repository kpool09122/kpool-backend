<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\CollaboratorDemotedMail;
use Application\Mail\CollaboratorPromotedMail;
use Application\Mail\DemotionWarningMail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Identity\Infrastructure\Service\CollaboratorNotificationService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class CollaboratorNotificationServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(CollaboratorNotificationServiceInterface::class);
        $this->assertInstanceOf(CollaboratorNotificationService::class, $service);
    }

    /**
     * @return array<string, array{Language}>
     */
    public static function languageProvider(): array
    {
        return [
            '日本語' => [Language::JAPANESE],
            '英語' => [Language::ENGLISH],
            '韓国語' => [Language::KOREAN],
        ];
    }

    /**
     * 正常系: sendDemotionWarningメソッドで各言語のメールが送信されること.
     */
    #[DataProvider('languageProvider')]
    public function testSendDemotionWarning(Language $language): void
    {
        Mail::fake();

        $service = new CollaboratorNotificationService();
        $email = new Email('test@example.com');

        $service->sendDemotionWarning($email, $language);

        Mail::assertSent(DemotionWarningMail::class, fn (DemotionWarningMail $mail) => $mail->hasTo((string) $email)
            && $mail->language === $language);
    }

    /**
     * 正常系: sendPromotionNotificationメソッドで各言語のメールが送信されること.
     */
    #[DataProvider('languageProvider')]
    public function testSendPromotionNotification(Language $language): void
    {
        Mail::fake();

        $service = new CollaboratorNotificationService();
        $email = new Email('test@example.com');

        $service->sendPromotionNotification($email, $language);

        Mail::assertSent(CollaboratorPromotedMail::class, fn (CollaboratorPromotedMail $mail) => $mail->hasTo((string) $email)
            && $mail->language === $language);
    }

    /**
     * 正常系: sendDemotionNotificationメソッドで各言語のメールが送信されること.
     */
    #[DataProvider('languageProvider')]
    public function testSendDemotionNotification(Language $language): void
    {
        Mail::fake();

        $service = new CollaboratorNotificationService();
        $email = new Email('test@example.com');

        $service->sendDemotionNotification($email, $language);

        Mail::assertSent(CollaboratorDemotedMail::class, fn (CollaboratorDemotedMail $mail) => $mail->hasTo((string) $email)
            && $mail->language === $language);
    }
}
