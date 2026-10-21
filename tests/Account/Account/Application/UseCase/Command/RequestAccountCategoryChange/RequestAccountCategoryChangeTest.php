<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidAccountCategoryChangeRequestException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;
use Source\Account\Account\Application\Service\AccountCategoryChangeRequestSnapshotServiceInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChange;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidator;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestAccount;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Account\Domain\ValueObject\NotificationSettings;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Phone;
use Tests\Helper\CreateAccountCategoryChangeRequestAccount;
use Tests\Helper\StrTestHelper;

class RequestAccountCategoryChangeTest extends TestCase
{
    public function testRejectsMissingAccountTypeBeforeCreatingRequest(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestRepository = new FakeRequestRepository();
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, [], accountType: null)),
            $requestRepository,
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(AccountSetupUnavailableException::class);

        try {
            $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
        } finally {
            $this->assertNull($requestRepository->saved);
        }
    }

    public function testCreatesPendingRequestWhenDocumentsSatisfyRequestedCategory(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountCategory::GENERAL, [
            DocumentType::BUSINESS_REGISTRATION,
            DocumentType::REPRESENTATIVE_ID,
        ]);
        $repository = new FakeRequestRepository();
        $snapshotService = new FakeSnapshotService();
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($account),
            $repository,
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            $snapshotService,
        );
        $output = new RequestAccountCategoryChangeOutput();

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), $output);

        $this->assertSame($account, $snapshotService->account);
        $this->assertSame($repository->saved, $snapshotService->request);
        $this->assertSame(1, $repository->findCalls);
        $this->assertNotNull($repository->saved);
        $this->assertSame(AccountCategoryChangeRequestStatus::PENDING, $repository->saved->status());
        $this->assertSame(AccountCategory::GENERAL, $repository->saved->currentAccountCategory());
        $this->assertSame(AccountCategory::AGENCY, $repository->saved->requestedAccountCategory());
        self::assertTrue(array_key_exists('requestIdentifier', $output->toArray()));
        $this->assertSame((string) $repository->saved->requestIdentifier(), $output->toArray()['requestIdentifier']);
    }

    public function testCapturesImmutableAccountDataAndPreservesOptionalAddressValues(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID], address: ContactAddress::fromArray([
            'countryCode' => 'FR',
            'postalCode' => '75001',
            'addressLine1' => '1 rue de Paris',
        ]));
        $repository = new FakeRequestRepository();
        $useCase = new RequestAccountCategoryChange(new FakeAccountRepository($account), $repository, new FixedFactory(), new AccountDocumentRequirementValidator(), new FakeSnapshotService());
        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
        self::assertNotNull($repository->saved);
        $snapshot = $repository->saved->accountSnapshot();
        $this->assertSame('75001', $snapshot->postalCode);
        $this->assertNull($snapshot->locality);
        $this->assertNull($snapshot->administrativeAreaCode);
        $this->assertNull($snapshot->addressLine2);
        $this->assertSame('FR', $snapshot->countryCode);
        $originalName = $snapshot->name;
        $originalPhone = $snapshot->phone;
        $account->changeName(new AccountName('Changed'));
        $account->changePhone(null);
        $account->changeAddress(null);
        $this->assertSame($originalName, $snapshot->name);
        $this->assertSame($originalPhone, $snapshot->phone);
        $this->assertSame('1 rue de Paris', $snapshot->addressLine1);
    }

    public function testThrowsWhenAccountIsMissing(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(new FakeAccountRepository(null), new FakeRequestRepository(), new FixedFactory(), new AccountDocumentRequirementValidator(), new FakeSnapshotService());

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
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(SameAccountCategoryChangeRequestException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::GENERAL), new RequestAccountCategoryChangeOutput());
    }

    /** @return array<string, array{DocumentType[]}> */
    public static function insufficientDocuments(): array
    {
        return [
            'empty' => [[]],
            'missing registration' => [[DocumentType::REPRESENTATIVE_ID]],
            'wrong account type' => [[DocumentType::PASSPORT, DocumentType::SELFIE]],
        ];
    }

    /** @param DocumentType[] $documentTypes */
    #[DataProvider('insufficientDocuments')]
    public function testThrowsWhenDocumentsDoNotSatisfyRequestedCategory(array $documentTypes): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, $documentTypes)),
            new FakeRequestRepository(),
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(InvalidDocumentsForVerificationException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    public function testThrowsWhenContactPhoneIsMissing(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID], phone: null)),
            new FakeRequestRepository(),
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(IncompleteAccountContactForCategoryChangeException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    /** @return iterable<string, array{string}> */
    public static function missingRequiredAddressFields(): iterable
    {
        yield 'postal code' => ['postalCode'];
    }

    #[DataProvider('missingRequiredAddressFields')]
    public function testRejectsMissingRequiredAddressFieldBeforeSavingRequest(string $field): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $address = ['countryCode' => 'FR', 'postalCode' => '75001', 'locality' => 'Paris', 'addressLine1' => '1 rue de Paris'];
        $address[$field] = null;
        $account = $this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID], address: ContactAddress::fromArray($address));
        $repository = new FakeRequestRepository();
        $useCase = new RequestAccountCategoryChange(new FakeAccountRepository($account), $repository, new FixedFactory(), new AccountDocumentRequirementValidator(), new FakeSnapshotService());
        $this->expectException(IncompleteAccountContactForCategoryChangeException::class);

        try {
            $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
        } finally {
            $this->assertNull($repository->saved);
        }
    }

    public function testThrowsWhenContactAddressCountryCodeIsMissing(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount(
                $accountId,
                AccountCategory::GENERAL,
                [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID],
                address: ContactAddress::fromArray([
                    'countryCode' => null,
                    'administrativeAreaCode' => null,
                    'postalCode' => '100-0001',
                    'locality' => '千代田区',
                    'addressLine1' => '千代田1-1',
                    'addressLine2' => null,
                ]),
            )),
            new FakeRequestRepository(),
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(IncompleteAccountContactForCategoryChangeException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    public function testThrowsWhenContactAddressLine1IsMissing(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount(
                $accountId,
                AccountCategory::GENERAL,
                [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID],
                address: ContactAddress::fromArray([
                    'countryCode' => 'JP',
                    'administrativeAreaCode' => '13',
                    'postalCode' => '100-0001',
                    'locality' => '千代田区',
                    'addressLine1' => null,
                    'addressLine2' => null,
                ]),
            )),
            new FakeRequestRepository(),
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(IncompleteAccountContactForCategoryChangeException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    public function testThrowsWhenAdministrativeAreaCodeIsMissingForRequiredCountry(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount(
                $accountId,
                AccountCategory::GENERAL,
                [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID],
                address: ContactAddress::fromArray([
                    'countryCode' => 'US',
                    'administrativeAreaCode' => null,
                    'postalCode' => '33139',
                    'locality' => 'Miami Beach',
                    'addressLine1' => '1 Ocean Dr',
                    'addressLine2' => null,
                ]),
            )),
            new FakeRequestRepository(),
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(IncompleteAccountContactForCategoryChangeException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    public function testAllowsAdministrativeAreaCodeNullForNonRequiredCountry(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $repository = new FakeRequestRepository();
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount(
                $accountId,
                AccountCategory::GENERAL,
                [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID],
                address: ContactAddress::fromArray([
                    'countryCode' => 'FR',
                    'administrativeAreaCode' => null,
                    'postalCode' => '75001',
                    'locality' => 'Paris',
                    'addressLine1' => '1 Rue de Rivoli',
                    'addressLine2' => null,
                ]),
            )),
            $repository,
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());

        $this->assertNotNull($repository->saved);
    }

    public function testThrowsWhenPendingRequestAlreadyExists(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $repository = new FakeRequestRepository();
        $repository->pendingRequest = (new FixedFactory())->create($accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, CreateAccountCategoryChangeRequestAccount::create());
        $useCase = new RequestAccountCategoryChange(
            new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID])),
            $repository,
            new FixedFactory(),
            new AccountDocumentRequirementValidator(),
            new FakeSnapshotService(),
        );

        $this->expectException(InvalidAccountCategoryChangeRequestException::class);

        $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
    }

    public function testApprovedHistoryPreventsNewRequestEvenWithLaterRejection(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $repository = new FakeRequestRepository();
        $approved = (new FixedFactory())->create($accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, CreateAccountCategoryChangeRequestAccount::create());
        $approved->approve($accountId);
        $rejected = (new FixedFactory())->create($accountId, AccountCategory::AGENCY, AccountCategory::TALENT, CreateAccountCategoryChangeRequestAccount::create());
        $rejected->reject($accountId, new RejectionReason(RejectionReasonCode::OTHER, 'reason'));
        $repository->history = [$approved, $rejected];
        $repository->pendingRequest = (new FixedFactory())->create($accountId, AccountCategory::AGENCY, AccountCategory::TALENT, CreateAccountCategoryChangeRequestAccount::create());
        $useCase = new RequestAccountCategoryChange(new FakeAccountRepository($this->createAccount($accountId, AccountCategory::AGENCY, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID])), $repository, new FixedFactory(), new AccountDocumentRequirementValidator(), new FakeSnapshotService());
        $this->expectException(InvalidAccountCategoryChangeRequestException::class);

        try {
            $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::TALENT), new RequestAccountCategoryChangeOutput());
        } finally {
            $this->assertNull($repository->saved);
        }
    }

    public function testRejectedAndWithdrawnHistoryAllowsNewIdentifierAndPreservesHistory(): void
    {
        foreach (['rejected', 'withdrawn'] as $status) {
            $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
            $repository = new FakeRequestRepository();
            $previous = (new FixedFactory())->create($accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, CreateAccountCategoryChangeRequestAccount::create());
            if ($status === 'withdrawn') {
                $previous->withdraw();
            } else {
                $previous->reject($accountId, new RejectionReason(RejectionReasonCode::OTHER, 'reason'));
            }
            $repository->history = [$previous];
            $useCase = new RequestAccountCategoryChange(new FakeAccountRepository($this->createAccount($accountId, AccountCategory::GENERAL, [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID])), $repository, new FixedFactory(), new AccountDocumentRequirementValidator(), new FakeSnapshotService());
            $useCase->process(new RequestAccountCategoryChangeInput($accountId, $this->createPrincipal($accountId), AccountCategory::AGENCY), new RequestAccountCategoryChangeOutput());
            $this->assertNotNull($repository->saved);
            $this->assertNotSame((string) $previous->requestIdentifier(), (string) $repository->saved->requestIdentifier());
            $this->assertSame($status, $previous->status()->value);
            $this->assertSame([$previous], $repository->history);
        }
    }

    /** @param DocumentType[] $documentTypes */
    private function createAccount(
        AccountIdentifier $accountId,
        AccountCategory $accountCategory,
        array $documentTypes,
        ?Phone $phone = new Phone('+81-90-1234-5678'),
        ?ContactAddress $address = null,
        ?AccountType $accountType = AccountType::CORPORATION,
    ): Account {
        $address ??= ContactAddress::fromArray([
            'countryCode' => 'JP',
            'administrativeAreaCode' => '13',
            'postalCode' => '100-0001',
            'locality' => '千代田区',
            'addressLine1' => '千代田1-1',
            'addressLine2' => null,
        ]);

        return new Account(
            $accountId,
            new Email('test@example.com'),
            $accountType,
            new AccountName('テストアカウント'),
            $accountType === null ? AccountStatus::PENDING : AccountStatus::ACTIVE,
            $accountCategory,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(array_map(
                static fn (DocumentType $documentType): AccountDocument => new AccountDocument($accountId, $documentType, new DocumentPath('/documents/test'), new DateTimeImmutable()),
                $documentTypes,
            )),
            new NotificationSettings([]),
            $phone,
            $address,
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

    public function findByIds(array $identifiers): array
    {
        if ($this->account === null) {
            return [];
        }
        foreach ($identifiers as $identifier) {
            if ((string) $identifier === (string) $this->account->accountIdentifier()) {
                return [$this->account];
            }
        }

        return [];
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
    /** @var AccountCategoryChangeRequest[] */
    public array $history = [];

    public ?AccountCategoryChangeRequest $saved = null;
    public int $findCalls = 0;

    public function save(AccountCategoryChangeRequest $request): void
    {
        $this->saved = $request;
    }

    public function findById(AccountCategoryChangeRequestIdentifier $id): ?AccountCategoryChangeRequest
    {
        return null;
    }

    /**
     * @param AccountCategoryChangeRequestStatus[] $statuses
     * @return AccountCategoryChangeRequest[]
     */
    public function findByAccountIdAndStatuses(AccountIdentifier $accountId, array $statuses): array
    {
        ++$this->findCalls;
        $requests = [];
        foreach ([$this->pendingRequest, ...$this->history] as $request) {
            if ($request !== null && in_array($request->status(), $statuses, true) && (string) $request->accountIdentifier() === (string) $accountId) {
                $requests[] = $request;
            }
        }

        return $requests;
    }
}

class FixedFactory implements AccountCategoryChangeRequestFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, AccountCategory $currentAccountCategory, AccountCategory $requestedAccountCategory, AccountCategoryChangeRequestAccount $accountSnapshot): AccountCategoryChangeRequest
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
            $accountSnapshot,
        );
    }
}

class FakeSnapshotService implements AccountCategoryChangeRequestSnapshotServiceInterface
{
    public ?Account $account = null;
    public ?AccountCategoryChangeRequest $request = null;

    public function capture(AccountCategoryChangeRequest $request, Account $account): void
    {
        $this->account = $account;
        $this->request = $request;
    }

    public function deleteForAccount(AccountIdentifier $accountIdentifier): void
    {
    }
}
