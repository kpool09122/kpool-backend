<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use Application\Mail\AccountCategoryChangeApprovedMail;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Source\Account\Account\Application\Service\AccountCategoryChangeNotificationServiceInterface;
use Source\Account\Account\Infrastructure\Service\AccountCategoryChangeNotificationService;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountCategoryChangeNotificationServiceTest extends TestCase
{
    public function testContainerResolvesNotificationService(): void
    {
        $this->assertInstanceOf(AccountCategoryChangeNotificationService::class, $this->app()->make(AccountCategoryChangeNotificationServiceInterface::class));
    }

    public function testSendsOnlyToTargetAccountRecipientsInTheirLanguages(): void
    {
        Mail::fake();
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
        $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $accountNotificationRecipientService->shouldReceive('resolve')->once()->with([$accountIdentifier])->andReturn([
            new AccountNotificationRecipient(new Email('japanese@example.com'), Language::JAPANESE),
            new AccountNotificationRecipient(new Email('english@example.com'), Language::ENGLISH),
        ]);

        (new AccountCategoryChangeNotificationService($accountNotificationRecipientService, 'https://frontend.example.com/'))
            ->sendApprovalNotification($accountIdentifier, AccountCategory::AGENCY);

        foreach (['japanese@example.com' => Language::JAPANESE, 'english@example.com' => Language::ENGLISH] as $email => $language) {
            Mail::assertQueued(AccountCategoryChangeApprovedMail::class, static fn (AccountCategoryChangeApprovedMail $mail): bool => $mail->hasTo($email)
                && $mail->language === $language
                && $mail->category === AccountCategory::AGENCY
                && $mail->categoryChangeUrl === 'https://frontend.example.com/admin/account/category-change');
        }
        Mail::assertQueuedCount(2);
    }

    public function testNoRecipientsMeansNoMail(): void
    {
        Mail::fake();
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
        $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $accountNotificationRecipientService->shouldReceive('resolve')->once()->with([$accountIdentifier])->andReturn([]);

        (new AccountCategoryChangeNotificationService($accountNotificationRecipientService, 'https://frontend.example.com'))
            ->sendApprovalNotification($accountIdentifier, AccountCategory::TALENT);

        Mail::assertNothingOutgoing();
    }

    public function testResolvesRecipientsAndSendsOnlyAfterCommit(): void
    {
        Mail::fake();
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $afterCommit = null;
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
        $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $accountNotificationRecipientService->shouldReceive('resolve')->once()->with([$accountIdentifier])->andReturn([
            new AccountNotificationRecipient(new Email('selected@example.com'), Language::KOREAN),
        ]);
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::on(static function (Closure $callback) use (&$afterCommit): bool {
            $afterCommit = $callback;

            return true;
        }));

        (new AccountCategoryChangeNotificationService($accountNotificationRecipientService, 'https://frontend.example.com'))
            ->sendApprovalNotification($accountIdentifier, AccountCategory::TALENT);

        Mail::assertNothingOutgoing();
        $this->assertInstanceOf(Closure::class, $afterCommit);
        $afterCommit();
        Mail::assertQueued(AccountCategoryChangeApprovedMail::class, static fn (AccountCategoryChangeApprovedMail $mail): bool => $mail->hasTo('selected@example.com') && $mail->category === AccountCategory::TALENT);
        Mail::assertQueuedCount(1);
    }

    public function testDoesNotSendWhenTransactionIsNotCommitted(): void
    {
        Mail::fake();
        /** @var AccountNotificationRecipientServiceInterface&Mockery\MockInterface $accountNotificationRecipientService */
        $accountNotificationRecipientService = Mockery::mock(AccountNotificationRecipientServiceInterface::class);
        $accountNotificationRecipientService->shouldNotReceive('resolve');
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        DB::shouldReceive('afterCommit')->once()->with(Mockery::type(Closure::class));

        (new AccountCategoryChangeNotificationService($accountNotificationRecipientService, 'https://frontend.example.com'))
            ->sendApprovalNotification(new AccountIdentifier(StrTestHelper::generateUuid()), AccountCategory::AGENCY);

        Mail::assertNothingOutgoing();
    }
}
