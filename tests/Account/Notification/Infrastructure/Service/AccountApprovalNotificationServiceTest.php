<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Infrastructure\Service;

use Application\Mail\AffiliationApprovedMail;
use Application\Mail\DelegationApprovedMail;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Source\Account\Notification\Application\Service\AccountApprovalNotificationServiceInterface;
use Source\Account\Notification\Infrastructure\Service\AccountApprovalNotificationService;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountApprovalNotificationServiceTest extends TestCase
{
    public function testContainerResolvesService(): void
    {
        $this->assertInstanceOf(AccountApprovalNotificationService::class, $this->app()->make(AccountApprovalNotificationServiceInterface::class));
    }

    public function testBothNotificationsSendOnlyConfiguredRecipientsInTheirLanguage(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        foreach ([true, false] as $affiliation) {
            Mail::fake();
            /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
            $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
            $recipients = array_map(static fn (Language $language): AccountNotificationRecipient => new AccountNotificationRecipient(new Email($language->value . '@example.com'), $language), Language::cases());
            $accountNotificationRecipientService->shouldReceive('resolve')->once()->with([$accountIdentifier])->andReturn($recipients);
            $service = new AccountApprovalNotificationService($accountNotificationRecipientService, 'https://frontend.example.com/');
            if ($affiliation) {
                $service->sendAffiliationApproval($accountIdentifier, 'Approver');
            } else {
                $service->sendDelegationApproval($accountIdentifier, 'Approver');
            }
            $mailClass = $affiliation ? AffiliationApprovedMail::class : DelegationApprovedMail::class;
            $url = 'https://frontend.example.com/admin/account/' . ($affiliation ? 'affiliations' : 'delegations');
            foreach ($recipients as $recipient) {
                Mail::assertSent($mailClass, static fn (AffiliationApprovedMail|DelegationApprovedMail $mail): bool => $mail->hasTo((string) $recipient->email())
                    && $mail->language === $recipient->language()
                    && $mail->approvalUrl === $url
                    && $mail->approverAccountName === 'Approver');
            }
            Mail::assertSentCount(count($recipients));
        }
    }

    public function testBothNotificationsSendNothingWhenRecipientsAreEmpty(): void
    {
        Mail::fake();
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
        $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $accountNotificationRecipientService->shouldReceive('resolve')->twice()->andReturn([]);
        $service = new AccountApprovalNotificationService($accountNotificationRecipientService, 'https://frontend.example.com');
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $service->sendAffiliationApproval($accountIdentifier, 'Approver');
        $service->sendDelegationApproval($accountIdentifier, 'Approver');
        Mail::assertNothingSent();
    }

    public function testResolvesRecipientsAndSendsAfterCommit(): void
    {
        Mail::fake();
        $afterCommit = null;
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
        $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $accountNotificationRecipientService->shouldReceive('resolve')->once()->with([$accountIdentifier])->andReturn([
            new AccountNotificationRecipient(new Email('selected@example.com'), Language::JAPANESE),
        ]);
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::on(static function (Closure $callback) use (&$afterCommit): bool {
            $afterCommit = $callback;

            return true;
        }));
        (new AccountApprovalNotificationService($accountNotificationRecipientService, 'https://frontend.example.com'))->sendAffiliationApproval($accountIdentifier, 'Approver');
        Mail::assertNothingSent();
        $this->assertInstanceOf(Closure::class, $afterCommit);
        $afterCommit();
        Mail::assertSent(AffiliationApprovedMail::class, static fn (AffiliationApprovedMail $mail): bool => $mail->hasTo('selected@example.com'));
        Mail::assertSentCount(1);
    }

    public function testDoesNotSendBeforeTransactionCommits(): void
    {
        Mail::fake();
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
        $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $accountNotificationRecipientService->shouldNotReceive('resolve');
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::type(Closure::class));
        (new AccountApprovalNotificationService($accountNotificationRecipientService, 'https://frontend.example.com'))->sendDelegationApproval(new AccountIdentifier(StrTestHelper::generateUuid()), 'Approver');
        Mail::assertNothingSent();
    }
}
