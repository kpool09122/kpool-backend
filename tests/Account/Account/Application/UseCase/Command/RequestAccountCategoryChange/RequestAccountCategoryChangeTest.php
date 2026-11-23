<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\Exception\InvalidAccountCategoryChangeRequestException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChange;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Account\Domain\ValueObject\NotificationSettings;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Phone;
use Tests\Helper\CreateAccountSnapshotProfile;
use Tests\Helper\StrTestHelper;

class RequestAccountCategoryChangeTest extends TestCase
{
    #[DataProvider('requestCases')]
    public function testRequestConstraintsAndSavedVersion(?string $failure, ?AccountCategoryChangeRequestStatus $history): void
    {
        $id = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount($id, AccountCategory::GENERAL, [], phone: null);
        $accountRepository = $this->createStub(AccountRepositoryInterface::class);
        $accountRepository->method('findById')->willReturn($failure === 'missing-account' ? null : $account);
        $repository = $this->createMock(AccountCategoryChangeRequestRepositoryInterface::class);
        $snapshotRepository = $this->createStub(AccountSnapshotRepositoryInterface::class);
        $snapshotId = new AccountSnapshotIdentifier(StrTestHelper::generateUuid());
        $snapshot = new AccountSnapshot($snapshotId, $id, 2, CreateAccountSnapshotProfile::create(), [], new DateTimeImmutable());
        $snapshotRepository->method('findCurrent')->willReturn($failure === 'missing-snapshot' ? null : $snapshot);
        $factory = $this->createMock(AccountCategoryChangeRequestFactoryInterface::class);
        $request = new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $id, AccountCategory::GENERAL, AccountCategory::AGENCY, AccountCategoryChangeRequestStatus::PENDING, new DateTimeImmutable(), null, null, null, $snapshotId);
        $historyRequest = $history === null ? null : new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $id, AccountCategory::GENERAL, AccountCategory::AGENCY, $history, new DateTimeImmutable(), null, null, null, $snapshotId);
        $repository->expects(in_array($failure, ['missing-account', 'foreign'], true) ? self::never() : self::once())->method('findByAccountIdAndStatuses')
            ->with($id, [AccountCategoryChangeRequestStatus::APPROVED, AccountCategoryChangeRequestStatus::PENDING])
            ->willReturn($failure === 'history' && $historyRequest !== null ? [$historyRequest] : []);
        if ($failure === null) {
            $factory->expects(self::once())->method('create')->with($id, AccountCategory::GENERAL, AccountCategory::AGENCY, $snapshotId)->willReturn($request);
            $repository->expects(self::once())->method('save')->with($request);
        } else {
            $factory->expects(self::never())->method('create');
            $repository->expects(self::never())->method('save');
            $this->expectException(match ($failure) {
                'missing-account' => AccountNotFoundException::class,
                'foreign' => AccountCategoryChangeRequestForbiddenException::class,
                'same' => SameAccountCategoryChangeRequestException::class,
                'history' => InvalidAccountCategoryChangeRequestException::class,
                default => AccountSnapshotNotFoundException::class,
            });
        }
        $output = new RequestAccountCategoryChangeOutput();
        (new RequestAccountCategoryChange($accountRepository, $repository, $factory, $snapshotRepository))->process(
            new RequestAccountCategoryChangeInput($id, $this->createPrincipal($failure === 'foreign' ? new AccountIdentifier(StrTestHelper::generateUuid()) : $id), $failure === 'same' ? AccountCategory::GENERAL : AccountCategory::AGENCY),
            $output,
        );
        $payload = $output->toArray();
        self::assertTrue(array_key_exists('snapshotIdentifier', $payload));
        self::assertSame((string) $snapshotId, $payload['snapshotIdentifier']);
    }

    /** @return iterable<string, array{?string, ?AccountCategoryChangeRequestStatus}> */
    public static function requestCases(): iterable
    {
        yield 'saved snapshot with incomplete live contact and no live documents' => [null, null];
        yield 'missing account' => ['missing-account', null];
        yield 'foreign actor' => ['foreign', null];
        yield 'same category' => ['same', null];
        yield 'missing saved snapshot' => ['missing-snapshot', null];
        yield 'pending blocks' => ['history', AccountCategoryChangeRequestStatus::PENDING];
        yield 'approved blocks' => ['history', AccountCategoryChangeRequestStatus::APPROVED];
        yield 'rejected allows' => [null, AccountCategoryChangeRequestStatus::REJECTED];
        yield 'withdrawn allows' => [null, AccountCategoryChangeRequestStatus::WITHDRAWN];
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
