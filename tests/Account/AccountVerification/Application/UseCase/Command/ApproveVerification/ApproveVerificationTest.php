<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AddressLine;
use Source\Account\Account\Domain\ValueObject\BillingAddress;
use Source\Account\Account\Domain\ValueObject\BillingContact;
use Source\Account\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Account\Domain\ValueObject\BillingMethod;
use Source\Account\Account\Domain\ValueObject\City;
use Source\Account\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Account\Domain\ValueObject\ContractName;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\Phone;
use Source\Account\Account\Domain\ValueObject\Plan;
use Source\Account\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Account\Domain\ValueObject\PlanName;
use Source\Account\Account\Domain\ValueObject\PostalCode;
use Source\Account\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Account\Domain\ValueObject\TaxRegion;
use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationInput;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationInterface;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Money;
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
        $result = $useCase->process($input);

        $this->assertTrue($result->isApproved());
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
        $useCase->process($input);
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

        $this->expectException(DomainException::class);

        $input = new ApproveVerificationInput($verificationId, $reviewerAccountId);
        $useCase->process($input);
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
        $billingAddress = new BillingAddress(
            CountryCode::JAPAN,
            new PostalCode('123-4567'),
            new StateOrProvince('Tokyo'),
            new City('Shibuya'),
            new AddressLine('1-2-3 Shibuya'),
            new AddressLine('Building A'),
            null,
        );

        $billingContact = new BillingContact(
            new ContractName('Test Contact'),
            new Email('test@example.com'),
            new Phone('+819012345678'),
        );

        $plan = new Plan(
            new PlanName('Standard Plan'),
            BillingCycle::MONTHLY,
            new PlanDescription('Standard monthly plan'),
            new Money(1000, Currency::JPY),
        );

        $taxInfo = new TaxInfo(
            TaxRegion::JP,
            TaxCategory::TAXABLE,
            'TAX001',
        );

        $contractInfo = new ContractInfo(
            $billingAddress,
            $billingContact,
            BillingMethod::CREDIT_CARD,
            $plan,
            $taxInfo,
            new DateTimeImmutable('2024-01-01'),
        );

        return new Account(
            $accountId,
            new Email('test@example.com'),
            AccountType::INDIVIDUAL,
            new AccountName('Test Account'),
            $contractInfo,
            AccountStatus::ACTIVE,
            $category,
            DeletionReadinessChecklist::ready(),
        );
    }
}
