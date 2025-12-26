<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\AuthCodeMail;
use Application\Mail\ConflictNotificationMail;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Infrastructure\Service\AuthCodeService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AuthCodeServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $authCodeService = $this->app->make(AuthCodeServiceInterface::class);
        $this->assertInstanceOf(AuthCodeService::class, $authCodeService);
    }

    /**
     * 正常系: generateCodeメソッドで6桁の認証コードが生成されること.
     *
     * @return void
     */
    public function testGenerateCode(): void
    {
        $authCodeService = new AuthCodeService();
        $email = new Email('test@example.com');

        $authCode = $authCodeService->generateCode($email);

        $this->assertInstanceOf(AuthCode::class, $authCode);
        $this->assertMatchesRegularExpression('/\A\d{6}\z/', (string) $authCode);
    }

    /**
     * 正常系: generateCodeメソッドで異なる認証コードが生成されること.
     *
     * @return void
     */
    public function testGenerateCodeReturnsDifferentCodes(): void
    {
        $authCodeService = new AuthCodeService();
        $email = new Email('test@example.com');

        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = (string) $authCodeService->generateCode($email);
        }

        $uniqueCodes = array_unique($codes);
        $this->assertGreaterThan(1, count($uniqueCodes));
    }

    /**
     * @return array<string, array{Language, string}>
     */
    public static function languageSubjectProvider(): array
    {
        return [
            '日本語' => [Language::JAPANESE, '認証コードのお知らせ'],
            '英語' => [Language::ENGLISH, 'Your Verification Code'],
            '韓国語' => [Language::KOREAN, '인증 코드 안내'],
        ];
    }

    /**
     * 正常系: sendメソッドで各言語のメールが送信されること.
     */
    #[DataProvider('languageSubjectProvider')]
    public function testSend(Language $language, string $expectedSubject): void
    {
        Mail::fake();

        $authCodeService = new AuthCodeService();
        $email = new Email('test@example.com');
        $authCode = new AuthCode('123456');
        $authCodeSession = new AuthCodeSession($email, $authCode, new DateTimeImmutable());

        $authCodeService->send($email, $language, $authCodeSession);

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use ($email, $authCodeSession, $language) {
            return $mail->hasTo((string) $email)
                && $mail->session === $authCodeSession
                && $mail->language === $language;
        });
    }

    /**
     * @return array<string, array{Language, string}>
     */
    public static function conflictLanguageSubjectProvider(): array
    {
        return [
            '日本語' => [Language::JAPANESE, '会員登録について'],
            '英語' => [Language::ENGLISH, 'About Your Registration'],
            '韓国語' => [Language::KOREAN, '회원가입 안내'],
        ];
    }

    /**
     * 正常系: notifyConflictメソッドで各言語のメールが送信されること.
     */
    #[DataProvider('conflictLanguageSubjectProvider')]
    public function testNotifyConflict(Language $language, string $expectedSubject): void
    {
        Mail::fake();

        $authCodeService = new AuthCodeService();
        $email = new Email('test@example.com');

        $authCodeService->notifyConflict($email, $language);

        Mail::assertSent(ConflictNotificationMail::class, function (ConflictNotificationMail $mail) use ($email, $language) {
            return $mail->hasTo((string) $email)
                && $mail->language === $language;
        });
    }
}
