<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UploadDocuments;

use DateTimeImmutable;
use Mockery;
use RuntimeException;
use Source\Account\Account\Application\Exception\AccountDocumentUploadForbiddenException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Service\AccountDocumentFileTypeDetectorInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\DocumentData;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInput;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInterface;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidator;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidatorInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UploadDocumentsTest extends TestCase
{
    public function testProcess(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $oldBusinessRegistrationPath = new DocumentPath('accounts/documents/old_business_registration.pdf');
        $oldRepresentativeIdPath = new DocumentPath('accounts/documents/old_representative_id.jpg');
        $account = $this->createAccount($accountId, AccountType::CORPORATION, [
            new AccountDocument(
                $accountId,
                DocumentType::BUSINESS_REGISTRATION,
                $oldBusinessRegistrationPath,
                new DateTimeImmutable('2026-07-01 00:00:00'),
            ),
            new AccountDocument(
                $accountId,
                DocumentType::REPRESENTATIVE_ID,
                $oldRepresentativeIdPath,
                new DateTimeImmutable('2026-07-01 00:00:00'),
            ),
        ]);
        $principal = $this->createPrincipal($accountId);
        $input = new UploadDocumentsInput($accountId, $principal, [
            new DocumentData(DocumentType::BUSINESS_REGISTRATION, 'business'),
            new DocumentData(DocumentType::REPRESENTATIVE_ID, 'representative'),
        ]);

        /** @var AccountRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($accountId)->andReturn($account);
        $repository->shouldReceive('save')->once()->with(Mockery::on(static fn (Account $saved): bool => count($saved->documents()->all()) === 2));

        /** @var DocumentStorageServiceInterface&\Mockery\MockInterface $storage */
        $storage = Mockery::mock(DocumentStorageServiceInterface::class);
        $storage
            ->shouldReceive('storeForAccount')
            ->once()
            ->with($accountId, DocumentType::BUSINESS_REGISTRATION, AccountDocumentFileType::PDF, 'business')
            ->andReturn(new DocumentPath('accounts/documents/business_registration.pdf'));
        $storage
            ->shouldReceive('storeForAccount')
            ->once()
            ->with($accountId, DocumentType::REPRESENTATIVE_ID, AccountDocumentFileType::JPEG, 'representative')
            ->andReturn(new DocumentPath('accounts/documents/representative_id.jpg'));
        $storage->shouldReceive('deleteAfterCommit')->once()->with($oldBusinessRegistrationPath);
        $storage->shouldReceive('deleteAfterCommit')->once()->with($oldRepresentativeIdPath);

        /** @var AccountDocumentFileTypeDetectorInterface&\Mockery\MockInterface $fileTypeDetector */
        $fileTypeDetector = Mockery::mock(AccountDocumentFileTypeDetectorInterface::class);
        $fileTypeDetector->shouldReceive('detect')->once()->with('business')->andReturn(AccountDocumentFileType::PDF);
        $fileTypeDetector->shouldReceive('detect')->once()->with('representative')->andReturn(AccountDocumentFileType::JPEG);

        $this->bindUseCaseDependencies($repository, $storage, $fileTypeDetector);

        $useCase = $this->app->make(UploadDocumentsInterface::class);
        $output = new UploadDocumentsOutput();
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertCount(2, $result['documents']);
        $this->assertSame('business_registration', $result['documents'][0]['documentType']);
    }

    public function testProcessDeletesStoredDocumentsWhenRepositorySaveFails(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountType::CORPORATION);
        $principal = $this->createPrincipal($accountId);
        $input = new UploadDocumentsInput($accountId, $principal, [
            new DocumentData(DocumentType::BUSINESS_REGISTRATION, 'business'),
            new DocumentData(DocumentType::REPRESENTATIVE_ID, 'representative'),
        ]);
        $businessRegistrationPath = new DocumentPath('accounts/documents/business_registration.pdf');
        $representativeIdPath = new DocumentPath('accounts/documents/representative_id.jpg');

        /** @var AccountRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($accountId)->andReturn($account);
        $repository->shouldReceive('save')->once()->andThrow(new RuntimeException('database error'));

        /** @var DocumentStorageServiceInterface&\Mockery\MockInterface $storage */
        $storage = Mockery::mock(DocumentStorageServiceInterface::class);
        $storage
            ->shouldReceive('storeForAccount')
            ->once()
            ->with($accountId, DocumentType::BUSINESS_REGISTRATION, AccountDocumentFileType::PDF, 'business')
            ->andReturn($businessRegistrationPath);
        $storage
            ->shouldReceive('storeForAccount')
            ->once()
            ->with($accountId, DocumentType::REPRESENTATIVE_ID, AccountDocumentFileType::JPEG, 'representative')
            ->andReturn($representativeIdPath);
        $storage->shouldReceive('delete')->once()->with($businessRegistrationPath)->andReturnTrue();
        $storage->shouldReceive('delete')->once()->with($representativeIdPath)->andReturnTrue();

        /** @var AccountDocumentFileTypeDetectorInterface&\Mockery\MockInterface $fileTypeDetector */
        $fileTypeDetector = Mockery::mock(AccountDocumentFileTypeDetectorInterface::class);
        $fileTypeDetector->shouldReceive('detect')->once()->with('business')->andReturn(AccountDocumentFileType::PDF);
        $fileTypeDetector->shouldReceive('detect')->once()->with('representative')->andReturn(AccountDocumentFileType::JPEG);

        $this->expectException(RuntimeException::class);

        $this->bindUseCaseDependencies($repository, $storage, $fileTypeDetector);

        $useCase = $this->app->make(UploadDocumentsInterface::class);
        $useCase->process($input, new UploadDocumentsOutput());
    }

    public function testProcessUploadsIndividualDocuments(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountType::INDIVIDUAL);
        $principal = $this->createPrincipal($accountId);
        $input = new UploadDocumentsInput($accountId, $principal, [
            new DocumentData(DocumentType::PASSPORT, 'passport'),
            new DocumentData(DocumentType::SELFIE, 'selfie'),
        ]);

        /** @var AccountRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($accountId)->andReturn($account);
        $repository->shouldReceive('save')->once()->with(Mockery::on(static fn (Account $saved): bool => count($saved->documents()->all()) === 2));

        /** @var DocumentStorageServiceInterface&\Mockery\MockInterface $storage */
        $storage = Mockery::mock(DocumentStorageServiceInterface::class);
        $storage
            ->shouldReceive('storeForAccount')
            ->once()
            ->with($accountId, DocumentType::PASSPORT, AccountDocumentFileType::PDF, 'passport')
            ->andReturn(new DocumentPath('accounts/documents/passport.pdf'));
        $storage
            ->shouldReceive('storeForAccount')
            ->once()
            ->with($accountId, DocumentType::SELFIE, AccountDocumentFileType::JPEG, 'selfie')
            ->andReturn(new DocumentPath('accounts/documents/selfie.jpg'));

        /** @var AccountDocumentFileTypeDetectorInterface&\Mockery\MockInterface $fileTypeDetector */
        $fileTypeDetector = Mockery::mock(AccountDocumentFileTypeDetectorInterface::class);
        $fileTypeDetector->shouldReceive('detect')->once()->with('passport')->andReturn(AccountDocumentFileType::PDF);
        $fileTypeDetector->shouldReceive('detect')->once()->with('selfie')->andReturn(AccountDocumentFileType::JPEG);

        $this->bindUseCaseDependencies($repository, $storage, $fileTypeDetector);

        $useCase = $this->app->make(UploadDocumentsInterface::class);
        $output = new UploadDocumentsOutput();
        $useCase->process($input, $output);

        $this->assertCount(2, $output->toArray()['documents']);
    }

    public function testProcessRejectsDifferentPrincipalAccount(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountType::CORPORATION);
        $principal = $this->createPrincipal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $input = new UploadDocumentsInput($accountId, $principal, [
            new DocumentData(DocumentType::BUSINESS_REGISTRATION, 'business'),
            new DocumentData(DocumentType::REPRESENTATIVE_ID, 'representative'),
        ]);

        /** @var AccountRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($accountId)->andReturn($account);

        $this->expectException(AccountDocumentUploadForbiddenException::class);

        /** @var DocumentStorageServiceInterface&\Mockery\MockInterface $storage */
        $storage = Mockery::mock(DocumentStorageServiceInterface::class);
        /** @var AccountDocumentFileTypeDetectorInterface&\Mockery\MockInterface $fileTypeDetector */
        $fileTypeDetector = Mockery::mock(AccountDocumentFileTypeDetectorInterface::class);

        $this->bindUseCaseDependencies($repository, $storage, $fileTypeDetector);

        $useCase = $this->app->make(UploadDocumentsInterface::class);
        $useCase->process($input, new UploadDocumentsOutput());
    }

    public function testProcessRejectsUnsupportedFileType(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountType::CORPORATION);
        $principal = $this->createPrincipal($accountId);
        $input = new UploadDocumentsInput($accountId, $principal, [
            new DocumentData(DocumentType::BUSINESS_REGISTRATION, 'plain text'),
            new DocumentData(DocumentType::REPRESENTATIVE_ID, 'representative'),
        ]);

        /** @var AccountRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($accountId)->andReturn($account);

        /** @var DocumentStorageServiceInterface&\Mockery\MockInterface $storage */
        $storage = Mockery::mock(DocumentStorageServiceInterface::class);
        $storage->shouldNotReceive('storeForAccount');

        /** @var AccountDocumentFileTypeDetectorInterface&\Mockery\MockInterface $fileTypeDetector */
        $fileTypeDetector = Mockery::mock(AccountDocumentFileTypeDetectorInterface::class);
        $fileTypeDetector
            ->shouldReceive('detect')
            ->once()
            ->with('plain text')
            ->andThrow(new InvalidDocumentsForVerificationException('Unsupported account document file type.'));

        $this->expectException(InvalidDocumentsForVerificationException::class);

        $this->bindUseCaseDependencies($repository, $storage, $fileTypeDetector);

        $useCase = $this->app->make(UploadDocumentsInterface::class);
        $useCase->process($input, new UploadDocumentsOutput());
    }

    private function bindUseCaseDependencies(
        AccountRepositoryInterface $repository,
        DocumentStorageServiceInterface $storage,
        AccountDocumentFileTypeDetectorInterface $fileTypeDetector,
    ): void {
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(DocumentStorageServiceInterface::class, $storage);
        $this->app->instance(AccountDocumentFileTypeDetectorInterface::class, $fileTypeDetector);
        $this->app->instance(
            AccountDocumentRequirementValidatorInterface::class,
            new AccountDocumentRequirementValidator(),
        );
    }

    private function createPrincipal(AccountIdentifier $accountId): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountId,
        );
    }

    /**
     * @param AccountDocument[] $documents
     */
    private function createAccount(AccountIdentifier $accountId, AccountType $type, array $documents = []): Account
    {
        return new Account(
            $accountId,
            new Email('account@example.com'),
            $type,
            new AccountName('Account'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments($documents),
        );
    }
}
