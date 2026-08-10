<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountTypeChange;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestAlreadyPendingException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\SameAccountTypeChangeRequestException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChange;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\Factory\AccountTypeChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountTypeChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidator;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
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

class RequestAccountTypeChangeTest extends TestCase
{
    public function testCreatesPendingRequestWhenDocumentsSatisfyRequestedType(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountType::INDIVIDUAL, [
            DocumentType::BUSINESS_REGISTRATION,
            DocumentType::REPRESENTATIVE_ID,
        ]);
        $repository = new FakeRequestRepository();
        $useCase = new RequestAccountTypeChange(
            new FakeAccountRepository($account),
            $repository,
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
        );
        $output = new RequestAccountTypeChangeOutput();

        $useCase->process(new RequestAccountTypeChangeInput($accountId, $this->createPrincipal($accountId), AccountType::CORPORATION), $output);

        $this->assertNotNull($repository->saved);
        $this->assertSame(AccountTypeChangeRequestStatus::PENDING, $repository->saved->status());
        $this->assertSame(AccountType::INDIVIDUAL, $repository->saved->currentAccountType());
        $this->assertSame(AccountType::CORPORATION, $repository->saved->requestedAccountType());
        $this->assertSame((string) $repository->saved->requestIdentifier(), $output->toArray()['requestIdentifier']);
    }

    public function testThrowsWhenAccountIsMissing(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountTypeChange(new FakeAccountRepository(null), new FakeRequestRepository(), new FixedFactory(), new AccountDocumentRequirementValidator());

        $this->expectException(AccountNotFoundException::class);

        $useCase->process(new RequestAccountTypeChangeInput($accountId, $this->createPrincipal($accountId), AccountType::CORPORATION), new RequestAccountTypeChangeOutput());
    }

    public function testThrowsWhenRequestedTypeIsSameAsCurrent(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountTypeChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountType::INDIVIDUAL, [DocumentType::PASSPORT, DocumentType::SELFIE])),
            new FakeRequestRepository(),
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
        );

        $this->expectException(SameAccountTypeChangeRequestException::class);

        $useCase->process(new RequestAccountTypeChangeInput($accountId, $this->createPrincipal($accountId), AccountType::INDIVIDUAL), new RequestAccountTypeChangeOutput());
    }

    public function testThrowsWhenDocumentsDoNotSatisfyRequestedType(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountTypeChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountType::INDIVIDUAL, [DocumentType::PASSPORT, DocumentType::SELFIE])),
            new FakeRequestRepository(),
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
        );

        $this->expectException(InvalidDocumentsForVerificationException::class);

        $useCase->process(new RequestAccountTypeChangeInput($accountId, $this->createPrincipal($accountId), AccountType::CORPORATION), new RequestAccountTypeChangeOutput());
    }

    public function testThrowsWhenPendingRequestAlreadyExists(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $repository = new FakeRequestRepository();
        $repository->pendingExists = true;
        $useCase = new RequestAccountTypeChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountType::INDIVIDUAL, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID])),
            $repository,
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
        );

        $this->expectException(AccountTypeChangeRequestAlreadyPendingException::class);

        $useCase->process(new RequestAccountTypeChangeInput($accountId, $this->createPrincipal($accountId), AccountType::CORPORATION), new RequestAccountTypeChangeOutput());
    }

    /** @param DocumentType[] $documentTypes */
    private function createAccount(AccountIdentifier $accountId, AccountType $accountType, array $documentTypes): Account
    {
        return new Account(
            $accountId,
            new Email('test@example.com'),
            $accountType,
            new AccountName('テストアカウント'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(array_map(
                static fn (DocumentType $documentType): AccountDocument => new AccountDocument($accountId, $documentType, new DocumentPath('/documents/test'), new DateTimeImmutable()),
                $documentTypes,
            )),
        );
    }

    private function createPrincipal(AccountIdentifier $accountId): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountId);
    }
}

class FakeAccountRepository implements AccountRepositoryInterface
{
    public function __construct(private ?Account $account)
    {
    }

    public function save(Account $account): void
    {
    }

    public function findById(AccountIdentifier $identifier): ?Account
    {
        return $this->account;
    }

    public function findByEmail(Email $email): ?Account
    {
        return null;
    }

    public function delete(Account $account): void
    {
    }
}

class FakeRequestRepository implements AccountTypeChangeRequestRepositoryInterface
{
    public bool $pendingExists = false;
    public ?AccountTypeChangeRequest $saved = null;

    public function save(AccountTypeChangeRequest $request): void
    {
        $this->saved = $request;
    }

    public function findById(AccountTypeChangeRequestIdentifier $id): ?AccountTypeChangeRequest
    {
        return null;
    }

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountTypeChangeRequest
    {
        return null;
    }

    public function existsPending(AccountIdentifier $accountId): bool
    {
        return $this->pendingExists;
    }
}

class FixedFactory implements AccountTypeChangeRequestFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, AccountType $currentAccountType, AccountType $requestedAccountType): AccountTypeChangeRequest
    {
        return new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $currentAccountType,
            $requestedAccountType,
            AccountTypeChangeRequestStatus::PENDING,
            new DateTimeImmutable('2026-08-11 00:00:00'),
            null,
            null,
            null,
        );
    }
}
