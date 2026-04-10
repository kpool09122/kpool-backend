<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationInput;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationOutput;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Exception\InvalidVerificationApprovalException;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveVerificationTest extends TestCase
{
    /**
     * 正常系: 本人認証を正しくApproveできること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());

        $verification = $this->createVerification($verificationId, $accountId, VerificationStatus::PENDING);
        $account = $this->createAccount($accountId, AccountCategory::GENERAL);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('findById')
            ->with($verificationId)
            ->once()
            ->andReturn($verification);
        $verificationRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);
        $accountRepository->shouldReceive('save')
            ->with($account)
            ->once()
            ->andReturnNull();

        $input = new ApproveVerificationInput($verificationId, $reviewerAccountId);

        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $useCase = $this->app->make(ApproveVerificationInterface::class);
        $output = new ApproveVerificationOutput();
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertSame(VerificationStatus::APPROVED->value, $result['status']);
        $this->assertSame(AccountCategory::TALENT, $account->accountCategory());
    }

    /**
     * 異常系: IDに紐づく本人認証が存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenVerificationNotFound(): void
    {
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository->shouldReceive('findById')
            ->with($verificationId)
            ->once()
            ->andReturn(null);
        $verificationRepository->shouldNotReceive('save');

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldNotReceive('findById');
        $accountRepository->shouldNotReceive('save');

        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $useCase = $this->app->make(ApproveVerificationInterface::class);

        $this->expectException(AccountVerificationNotFoundException::class);

        $input = new ApproveVerificationInput($verificationId, $reviewerAccountId);
        $useCase->process($input, new ApproveVerificationOutput());
    }

    /**
     * 異常系: すでに承認されている場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenAlreadyApproved(): void
    {
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());

        $verification = $this->createVerification($verificationId, $accountId, VerificationStatus::APPROVED);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('findById')
            ->with($verificationId)
            ->once()
            ->andReturn($verification);
        $verificationRepository->shouldNotReceive('save');

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldNotReceive('findById');
        $accountRepository->shouldNotReceive('save');

        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $useCase = $this->app->make(ApproveVerificationInterface::class);

        $this->expectException(InvalidVerificationApprovalException::class);

        $input = new ApproveVerificationInput($verificationId, $reviewerAccountId);
        $useCase->process($input, new ApproveVerificationOutput());
    }

    private function createVerification(
        VerificationIdentifier $verificationId,
        AccountIdentifier $accountId,
        VerificationStatus $status,
    ): AccountVerification {
        return new AccountVerification(
            $verificationId,
            $accountId,
            VerificationType::TALENT,
            $status,
            new ApplicantInfo('Taro Yamada'),
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }

    private function createAccount(AccountIdentifier $accountId, AccountCategory $category): Account
    {
        return new Account(
            $accountId,
            new Email('test@example.com'),
            AccountType::INDIVIDUAL,
            new AccountName('Test Account'),
            AccountStatus::ACTIVE,
            $category,
            DeletionReadinessChecklist::ready(),
        );
    }
}
