<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestAlreadyPendingException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChange;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Service\DocumentRequirementValidator;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
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

class RequestAccountCategoryChangeTest extends TestCase
{
    public function testCreatesPendingRequestWhenDocumentsSatisfyRequestedCategory(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountCategory::GENERAL, [
            DocumentType::BUSINESS_REGISTRATION,
            DocumentType::REPRESENTATIVE_ID,
        ]);
        $repository = new FakeRequestRepository();
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($account),
            $repository,
            new FixedFactory(),
            new DocumentRequirementValidator(),
        );
        $output = new RequestAccountCategoryChangeOutput();

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), $output);

        $this->assertNotNull($repository->saved);
        $this->assertSame(AccountCategoryChangeRequestStatus::PENDING, $repository->saved->status());
        $this->assertSame(AccountCategory::GENERAL, $repository->saved->currentAccountCategory());
        $this->assertSame(AccountCategory::AGENCY, $repository->saved->requestedAccountCategory());
        $this->assertSame((string) $repository->saved->requestIdentifier(), $output->toArray()['requestIdentifier']);
    }

    public function testThrowsWhenAccountIsMissing(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(new FakeAccountRepository(null), new FakeRequestRepository(), new FixedFactory(), new DocumentRequirementValidator());

        $this->expectException(AccountNotFoundException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    public function testThrowsWhenRequestedCategoryIsSameAsCurrent(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::PASSPORT, DocumentType::SELFIE])),
            new FakeRequestRepository(),
            new FixedFactory(),
            new DocumentRequirementValidator(),
        );

        $this->expectException(SameAccountCategoryChangeRequestException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::GENERAL), new RequestAccountCategoryChangeOutput());
    }

    public function testThrowsWhenDocumentsDoNotSatisfyRequestedCategory(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::PASSPORT, DocumentType::SELFIE])),
            new FakeRequestRepository(),
            new FixedFactory(),
            new DocumentRequirementValidator(),
        );

        $this->expectException(InvalidDocumentsForVerificationException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    public function testThrowsWhenPendingRequestAlreadyExists(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $repository = new FakeRequestRepository();
        $repository->pendingRequest = (new FixedFactory())->create($accountId, AccountCategory::GENERAL, AccountCategory::AGENCY);
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID])),
            $repository,
            new FixedFactory(),
            new DocumentRequirementValidator(),
        );

        $this->expectException(AccountCategoryChangeRequestAlreadyPendingException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    /** @param DocumentType[] $documentTypes */
    private function createAccount(AccountIdentifier $accountId, AccountCategory $accountCategory, array $documentTypes): Account
    {
        return new Account(
            $accountId,
            new Email('test@example.com'),
            AccountType::INDIVIDUAL,
            new AccountName('テストアカウント'),
            AccountStatus::ACTIVE,
            $accountCategory,
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

class FakeRequestRepository implements AccountCategoryChangeRequestRepositoryInterface
{
    public ?AccountCategoryChangeRequest $pendingRequest = null;
    public ?AccountCategoryChangeRequest $saved = null;

    public function save(AccountCategoryChangeRequest $request): void
    {
        $this->saved = $request;
    }

    public function findById(AccountCategoryChangeRequestIdentifier $id): ?AccountCategoryChangeRequest
    {
        return null;
    }

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountCategoryChangeRequest
    {
        return $this->pendingRequest;
    }
}

class FixedFactory implements AccountCategoryChangeRequestFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, AccountCategory $currentAccountCategory, AccountCategory $requestedAccountCategory): AccountCategoryChangeRequest
    {
        return new AccountCategoryChangeRequest(
            new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $currentAccountCategory,
            $requestedAccountCategory,
            AccountCategoryChangeRequestStatus::PENDING,
            new DateTimeImmutable('2026-08-11 00:00:00'),
            null,
            null,
            null,
        );
    }
}
