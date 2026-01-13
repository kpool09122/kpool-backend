<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
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
use Source\Account\AccountVerification\Application\Exception\AccountVerificationAlreadyRequestedException;
use Source\Account\AccountVerification\Application\Exception\DocumentStorageFailedException;
use Source\Account\AccountVerification\Application\Exception\InvalidAccountCategoryForVerificationException;
use Source\Account\AccountVerification\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\AccountVerification\Application\Service\DocumentStorageServiceInterface;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\DocumentData;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerificationInput;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerificationInterface;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Factory\AccountVerificationFactoryInterface;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\AccountVerification\Domain\Service\DocumentRequirementValidator;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestVerificationTest extends TestCase
{
    /**
     * 正常系: TALENTの本人認証を正しくリクエストできること（ID + セルフィー）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWithTalentDocuments(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');

        $documents = [
            new DocumentData(DocumentType::PASSPORT, 'passport.jpg', 'file-contents', 1024),
            new DocumentData(DocumentType::SELFIE, 'selfie.jpg', 'file-contents', 512),
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);
        $verification = $this->createVerification($accountId, $verificationType, $applicantInfo);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(false);
        $verificationRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($verification);

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService
            ->shouldReceive('store')
            ->twice()
            ->andReturn(new DocumentPath('verifications/test/document.jpg'));

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator
            ->shouldReceive('generate')
            ->twice()
            ->andReturn(StrTestHelper::generateUuid(), StrTestHelper::generateUuid());

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(AccountVerification::class, $result);
        $this->assertSame(VerificationStatus::PENDING, $result->status());
        $this->assertCount(2, $result->documents());
    }

    /**
     * 正常系: AGENCYの本人認証を正しくリクエストできること（法人書類 + 代表者ID）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWithAgencyDocuments(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::AGENCY;
        $applicantInfo = new ApplicantInfo('Test Agency Corp', 'Test Company');

        $documents = [
            new DocumentData(DocumentType::BUSINESS_REGISTRATION, 'business.pdf', 'file-contents', 2048),
            new DocumentData(DocumentType::REPRESENTATIVE_ID, 'representative.jpg', 'file-contents', 1024),
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);
        $verification = $this->createVerification($accountId, $verificationType, $applicantInfo);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(false);
        $verificationRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($verification);

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService
            ->shouldReceive('store')
            ->twice()
            ->andReturn(new DocumentPath('verifications/test/document.pdf'));

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator
            ->shouldReceive('generate')
            ->twice()
            ->andReturn(StrTestHelper::generateUuid(), StrTestHelper::generateUuid());

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);
        $result = $useCase->process($input);

        $this->assertInstanceOf(AccountVerification::class, $result);
        $this->assertSame(VerificationStatus::PENDING, $result->status());
        $this->assertCount(2, $result->documents());
    }

    /**
     * 異常系: アカウントカテゴリがGENERALでない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenAccountIsNotGeneral(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');
        $documents = [
            new DocumentData(DocumentType::PASSPORT, 'passport.jpg', 'file-contents', 1024),
            new DocumentData(DocumentType::SELFIE, 'selfie.jpg', 'file-contents', 512),
        ];

        $account = $this->createAccount($accountId, AccountCategory::TALENT);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository->shouldNotReceive('existsPending');
        $verificationRepository->shouldNotReceive('save');

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory->shouldNotReceive('create');

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService->shouldNotReceive('store');

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);

        $this->expectException(InvalidAccountCategoryForVerificationException::class);

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);
        $useCase->process($input);
    }

    /**
     * 異常系: 既に申請中の本人認証が存在する場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenPendingVerificationExists(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');
        $documents = [
            new DocumentData(DocumentType::PASSPORT, 'passport.jpg', 'file-contents', 1024),
            new DocumentData(DocumentType::SELFIE, 'selfie.jpg', 'file-contents', 512),
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(true);
        $verificationRepository->shouldNotReceive('save');

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory->shouldNotReceive('create');

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService->shouldNotReceive('store');

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);

        $this->expectException(AccountVerificationAlreadyRequestedException::class);

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);
        $useCase->process($input);
    }

    /**
     * 異常系: TALENTでセルフィーが不足している場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenTalentMissingSelfie(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');
        $documents = [
            new DocumentData(DocumentType::PASSPORT, 'passport.jpg', 'file-contents', 1024),
            // Missing SELFIE
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(false);
        $verificationRepository->shouldNotReceive('save');

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory->shouldNotReceive('create');

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService->shouldNotReceive('store');

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);

        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->expectExceptionMessage('Selfie is required for talent verification.');

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);
        $useCase->process($input);
    }

    /**
     * 異常系: TALENTでID書類が不足している場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenTalentMissingIdDocument(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');
        $documents = [
            new DocumentData(DocumentType::SELFIE, 'selfie.jpg', 'file-contents', 512),
            // Missing ID document
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(false);
        $verificationRepository->shouldNotReceive('save');

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory->shouldNotReceive('create');

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService->shouldNotReceive('store');

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);

        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->expectExceptionMessage('ID document is required for talent verification.');

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);
        $useCase->process($input);
    }

    /**
     * 異常系: AGENCYで法人書類が不足している場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenAgencyMissingBusinessDocument(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::AGENCY;
        $applicantInfo = new ApplicantInfo('Test Agency Corp', 'Test Company');
        $documents = [
            new DocumentData(DocumentType::REPRESENTATIVE_ID, 'representative.jpg', 'file-contents', 1024),
            // Missing business document
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(false);
        $verificationRepository->shouldNotReceive('save');

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory->shouldNotReceive('create');

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService->shouldNotReceive('store');

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);

        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->expectExceptionMessage('Business document is required for agency verification.');

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);
        $useCase->process($input);
    }

    /**
     * 異常系: ドキュメント保存に失敗した場合、DocumentStorageFailedExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsDocumentStorageFailedExceptionWhenStorageFails(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');
        $documents = [
            new DocumentData(DocumentType::PASSPORT, 'passport.jpg', 'file-contents', 1024),
            new DocumentData(DocumentType::SELFIE, 'selfie.jpg', 'file-contents', 512),
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);
        $verification = $this->createVerification($accountId, $verificationType, $applicantInfo);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(false);
        $verificationRepository->shouldNotReceive('save');

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($verification);

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService
            ->shouldReceive('store')
            ->once()
            ->andThrow(new RuntimeException('S3 connection failed'));
        $storageService->shouldNotReceive('delete');

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);

        $this->expectException(DocumentStorageFailedException::class);
        $this->expectExceptionMessage('Failed to store verification documents: S3 connection failed');

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);
        $useCase->process($input);
    }

    /**
     * 異常系: 2番目のドキュメント保存に失敗した場合、1番目のドキュメントがクリーンアップされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCleansUpStoredFilesWhenStorageFails(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $verificationType = VerificationType::TALENT;
        $applicantInfo = new ApplicantInfo('Taro Yamada');
        $documents = [
            new DocumentData(DocumentType::PASSPORT, 'passport.jpg', 'file-contents', 1024),
            new DocumentData(DocumentType::SELFIE, 'selfie.jpg', 'file-contents', 512),
        ];

        $account = $this->createAccount($accountId, AccountCategory::GENERAL);
        $verification = $this->createVerification($accountId, $verificationType, $applicantInfo);

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository
            ->shouldReceive('findById')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('existsPending')
            ->with($accountId)
            ->once()
            ->andReturn(false);
        $verificationRepository->shouldNotReceive('save');

        $verificationFactory = Mockery::mock(AccountVerificationFactoryInterface::class);
        $verificationFactory
            ->shouldReceive('create')
            ->once()
            ->andReturn($verification);

        $firstDocumentPath = new DocumentPath('verifications/test/passport.jpg');

        $storageService = Mockery::mock(DocumentStorageServiceInterface::class);
        $storageService
            ->shouldReceive('store')
            ->once()
            ->andReturn($firstDocumentPath);
        $storageService
            ->shouldReceive('store')
            ->once()
            ->andThrow(new RuntimeException('S3 connection failed'));
        $storageService
            ->shouldReceive('delete')
            ->with($firstDocumentPath)
            ->once();

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn(StrTestHelper::generateUuid());

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $this->app->instance(AccountVerificationFactoryInterface::class, $verificationFactory);
        $this->app->instance(DocumentStorageServiceInterface::class, $storageService);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(DocumentRequirementValidator::class, new DocumentRequirementValidator());

        $useCase = $this->app->make(RequestVerificationInterface::class);

        $this->expectException(DocumentStorageFailedException::class);

        $input = new RequestVerificationInput($accountId, $verificationType, $applicantInfo, $documents);
        $useCase->process($input);
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

    private function createVerification(
        AccountIdentifier $accountId,
        VerificationType $type,
        ApplicantInfo $applicantInfo,
    ): AccountVerification {
        return new AccountVerification(
            new VerificationIdentifier(StrTestHelper::generateUuid()),
            $accountId,
            $type,
            VerificationStatus::PENDING,
            $applicantInfo,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
