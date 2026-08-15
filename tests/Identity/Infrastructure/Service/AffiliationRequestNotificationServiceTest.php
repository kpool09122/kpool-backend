<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\AffiliationRequestMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Source\Identity\Application\Service\AffiliationRequestNotificationServiceInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Infrastructure\Service\AffiliationRequestNotificationService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationRequestNotificationServiceTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addLocation(dirname(__DIR__, 4) . '/resources/views');
    }

    public function test__construct(): void
    {
        $service = $this->app->make(AffiliationRequestNotificationServiceInterface::class);

        $this->assertInstanceOf(AffiliationRequestNotificationService::class, $service);
    }

    public function testSendAffiliationRequestNotification(): void
    {
        Mail::fake();
        $email = new Email('target@example.com');
        /** @var IdentityRepositoryInterface&\Mockery\MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturn($this->identity($email, Language::JAPANESE));

        $service = new AffiliationRequestNotificationService($identityRepository, 'http://localhost:3000');
        $service->sendAffiliationRequestNotification($email);

        Mail::assertSent(AffiliationRequestMail::class, static fn (AffiliationRequestMail $mail): bool => $mail->hasTo((string) $email)
            && $mail->language === Language::JAPANESE
            && $mail->affiliationUrl === 'http://localhost:3000/admin/account/affiliations');
    }

    public function testSendAffiliationRequestNotificationDoesNothingWhenIdentityIsMissing(): void
    {
        Mail::fake();
        $email = new Email('target@example.com');
        /** @var IdentityRepositoryInterface&\Mockery\MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturn(null);

        $service = new AffiliationRequestNotificationService($identityRepository, 'https://frontend.example.com');
        $service->sendAffiliationRequestNotification($email);

        Mail::assertNothingSent();
    }

    public function testMailBodyContainsAffiliationUrl(): void
    {
        Mail::fake();
        $email = new Email('target@example.com');
        /** @var IdentityRepositoryInterface&\Mockery\MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturn($this->identity($email, Language::ENGLISH));

        $service = new AffiliationRequestNotificationService($identityRepository, 'https://frontend.example.com');
        $service->sendAffiliationRequestNotification($email);

        Mail::assertSent(AffiliationRequestMail::class, static fn (AffiliationRequestMail $mail): bool => str_contains($mail->affiliationUrl, '/admin/account/affiliations'));
    }

    public function testSendAffiliationRequestNotificationDefersUntilAfterCommitInTransaction(): void
    {
        Mail::fake();
        $email = new Email('target@example.com');
        /** @var IdentityRepositoryInterface&\Mockery\MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturn($this->identity($email, Language::ENGLISH));

        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::type('Closure'));

        $service = new AffiliationRequestNotificationService($identityRepository, 'https://frontend.example.com');
        $service->sendAffiliationRequestNotification($email);

        Mail::assertNothingSent();
    }

    public function testMailBodyRendersHtmlForAllLanguages(): void
    {
        $affiliationUrl = 'https://frontend.example.com/admin/account/affiliations';

        foreach (Language::cases() as $language) {
            $html = (new AffiliationRequestMail($affiliationUrl, $language))->render();

            $this->assertStringContainsString('<!DOCTYPE html>', $html);
            $this->assertStringContainsString('<html lang="' . $language->value . '">', $html);
            $this->assertStringContainsString('href="' . $affiliationUrl . '"', $html);
            $this->assertStringContainsString($affiliationUrl, $html);
        }
    }

    private function identity(Email $email, Language $language): Identity
    {
        return new Identity(new IdentityIdentifier(StrTestHelper::generateUuid()), new IdentityName('Target'), $email, $language, null, new HashedPassword(password_hash('password', PASSWORD_BCRYPT)), null);
    }
}
