<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Mail\AffiliationRequestMail;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Source\Identity\Infrastructure\Service\AffiliationRequestNotificationService;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationRequestNotificationServiceTest extends TestCase
{
    public function testSendsOnlyConfiguredRecipientsInTheirLanguage(): void
    {
        Mail::fake();
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $resolver->shouldReceive('resolve')->once()->with([$account])->andReturn([new AccountNotificationRecipient(new Email('selected@example.com'), Language::ENGLISH)]);
        (new AffiliationRequestNotificationService($resolver, 'https://frontend.example.com'))->sendAffiliationRequestNotification($account);
        Mail::assertSent(AffiliationRequestMail::class, static fn (AffiliationRequestMail $mail): bool => $mail->hasTo('selected@example.com') && $mail->language === Language::ENGLISH && $mail->affiliationUrl === 'https://frontend.example.com/admin/account/affiliations');
        Mail::assertSentCount(1);
    }

    public function testUnsetRecipientsDoNotSendMail(): void
    {
        Mail::fake();
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $resolver->shouldReceive('resolve')->once()->with([$account])->andReturn([]);
        (new AffiliationRequestNotificationService($resolver, 'https://frontend.example.com'))->sendAffiliationRequestNotification($account);
        Mail::assertNothingSent();
    }

    public function testDefersResolutionAndSendingUntilAfterCommit(): void
    {
        Mail::fake();
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $resolver */
        $resolver = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $resolver->shouldNotReceive('resolve');
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::type(Closure::class));
        (new AffiliationRequestNotificationService($resolver, 'https://frontend.example.com'))->sendAffiliationRequestNotification($account);
        Mail::assertNothingSent();
    }
}
