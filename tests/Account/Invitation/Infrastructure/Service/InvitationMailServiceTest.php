<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Infrastructure\Service;

use Application\Mail\InvitationMail;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Service\InvitationMailServiceInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Account\Invitation\Infrastructure\Service\InvitationMailService;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class InvitationMailServiceTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(InvitationMailServiceInterface::class);
        $this->assertInstanceOf(InvitationMailService::class, $service);
    }

    /**
     * 正常系: 招待メールが正しく送信されること
     *
     * @throws BindingResolutionException
     */
    public function testSendInvitationEmail(): void
    {
        Mail::fake();

        $data = $this->createTestData();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->once()
            ->with($data->accountIdentifier)
            ->andReturn($data->account);

        $identity = Mockery::mock(Identity::class);
        $identity->shouldReceive('language')
            ->once()
            ->andReturn(Language::JAPANESE);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($data->inviterIdentityIdentifier)
            ->andReturn($identity);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);

        $service = $this->app->make(InvitationMailServiceInterface::class);
        $service->sendInvitationEmail($data->invitation);

        Mail::assertSent(InvitationMail::class, static function (InvitationMail $mail) use ($data) {
            return $mail->hasTo((string) $data->invitation->email())
                && $mail->invitation === $data->invitation
                && $mail->accountName === (string) $data->account->name()
                && $mail->language === Language::JAPANESE
                && str_contains($mail->invitationUrl, (string) $data->invitation->token());
        });
    }

    /**
     * 正常系: アカウントが見つからない場合、デフォルト名で招待メールが送信されること
     *
     * @throws BindingResolutionException
     */
    public function testSendInvitationEmailWithAccountNotFound(): void
    {
        Mail::fake();

        $data = $this->createTestData();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->once()
            ->with($data->accountIdentifier)
            ->andReturnNull();

        $identity = Mockery::mock(Identity::class);
        $identity->shouldReceive('language')
            ->once()
            ->andReturn(Language::JAPANESE);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($data->inviterIdentityIdentifier)
            ->andReturn($identity);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);

        $service = $this->app->make(InvitationMailServiceInterface::class);
        $service->sendInvitationEmail($data->invitation);

        Mail::assertSent(InvitationMail::class, static function (InvitationMail $mail) use ($data) {
            return $mail->hasTo((string) $data->invitation->email())
                && $mail->invitation === $data->invitation
                && $mail->accountName === 'アカウント'
                && $mail->language === Language::JAPANESE
                && str_contains($mail->invitationUrl, (string) $data->invitation->token());
        });
    }

    /**
     * 正常系: 招待URLにトークンが含まれていること
     *
     * @throws BindingResolutionException
     */
    public function testSendInvitationEmailContainsCorrectUrl(): void
    {
        Mail::fake();

        $data = $this->createTestData();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->once()
            ->with($data->accountIdentifier)
            ->andReturn($data->account);

        $identity = Mockery::mock(Identity::class);
        $identity->shouldReceive('language')
            ->once()
            ->andReturn(Language::JAPANESE);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($data->inviterIdentityIdentifier)
            ->andReturn($identity);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);

        $service = $this->app->make(InvitationMailServiceInterface::class);
        $service->sendInvitationEmail($data->invitation);

        Mail::assertSent(InvitationMail::class, static function (InvitationMail $mail) use ($data) {
            return str_contains($mail->invitationUrl, '/signup?token=')
                && str_contains($mail->invitationUrl, (string) $data->invitation->token());
        });
    }

    private function createTestData(): InvitationMailServiceTestData
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');
        $token = new InvitationToken(bin2hex(random_bytes(32)));

        $invitation = new Invitation(
            new InvitationIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
            $token,
            InvitationStatus::PENDING,
            new DateTimeImmutable('+7 days'),
            null,
            null,
            new DateTimeImmutable(),
        );

        $account = new Account(
            $accountIdentifier,
            new Email('account@example.com'),
            AccountType::INDIVIDUAL,
            new AccountName('テストアカウント'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );

        return new InvitationMailServiceTestData(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $invitation,
            $account,
        );
    }
}

readonly class InvitationMailServiceTestData
{
    public function __construct(
        public AccountIdentifier $accountIdentifier,
        public IdentityIdentifier $inviterIdentityIdentifier,
        public Invitation $invitation,
        public Account $account,
    ) {
    }
}
