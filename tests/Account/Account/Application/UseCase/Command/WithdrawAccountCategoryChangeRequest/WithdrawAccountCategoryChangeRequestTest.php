<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest\WithdrawAccountCategoryChangeRequest;
use Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest\WithdrawAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest\WithdrawAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest\WithdrawAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestWithdrawalException;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Infrastructure\Repository\AccountCategoryChangeRequestRepository;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountCategoryChangeRequestAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WithdrawAccountCategoryChangeRequestTest extends TestCase
{
    public function testBinding(): void
    {
        $this->assertInstanceOf(WithdrawAccountCategoryChangeRequest::class, $this->app()->make(WithdrawAccountCategoryChangeRequestInterface::class));
    }

    public function testOwnerCanWithdrawWithoutManagementPermission(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $request = $this->request($accountId, AccountCategoryChangeRequestStatus::PENDING);
        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($request->requestIdentifier())->andReturn($request);
        $repository->shouldReceive('save')->once()->with($request);
        $output = new WithdrawAccountCategoryChangeRequestOutput();
        (new WithdrawAccountCategoryChangeRequest($repository))->process(new WithdrawAccountCategoryChangeRequestInput($accountId, $request->requestIdentifier(), $this->principal($accountId)), $output);
        $payload = $output->toArray();
        $this->assertArrayHasKey('status', $payload);
        $this->assertSame('withdrawn', $payload['status'] ?? null);
        $payload = $output->toArray();
        $this->assertArrayHasKey('currentAccountCategory', $payload);
        $this->assertSame('general', $payload['currentAccountCategory'] ?? null);
        $this->assertArrayHasKey('reviewedBy', $payload);
        $this->assertArrayHasKey('reviewedAt', $payload);
        $this->assertNull($payload['reviewedBy'] ?? null);
        $this->assertNull($payload['reviewedAt'] ?? null);
    }

    public function testAnotherPrincipalIsForbiddenBeforeLookup(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        $repository->shouldNotReceive('save');
        $this->expectException(AccountCategoryChangeRequestForbiddenException::class);
        (new WithdrawAccountCategoryChangeRequest($repository))->process(new WithdrawAccountCategoryChangeRequestInput($accountId, new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()))), new WithdrawAccountCategoryChangeRequestOutput());
    }

    public function testRequestBelongingToAnotherAccountIsForbidden(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $request = $this->request(new AccountIdentifier(StrTestHelper::generateUuid()), AccountCategoryChangeRequestStatus::PENDING);
        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($request);
        $repository->shouldNotReceive('save');
        $this->expectException(AccountCategoryChangeRequestForbiddenException::class);
        (new WithdrawAccountCategoryChangeRequest($repository))->process(new WithdrawAccountCategoryChangeRequestInput($accountId, $request->requestIdentifier(), $this->principal($accountId)), new WithdrawAccountCategoryChangeRequestOutput());
    }

    public function testMissingRequestIsNotFound(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturnNull();
        $repository->shouldNotReceive('save');
        $this->expectException(AccountCategoryChangeRequestNotFoundException::class);
        (new WithdrawAccountCategoryChangeRequest($repository))->process(new WithdrawAccountCategoryChangeRequestInput($accountId, new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $this->principal($accountId)), new WithdrawAccountCategoryChangeRequestOutput());
    }

    /** @return array<string, array{AccountCategoryChangeRequestStatus}> */
    public static function terminalStates(): array
    {
        return ['approved' => [AccountCategoryChangeRequestStatus::APPROVED], 'rejected' => [AccountCategoryChangeRequestStatus::REJECTED], 'withdrawn' => [AccountCategoryChangeRequestStatus::WITHDRAWN]];
    }

    #[DataProvider('terminalStates')]
    public function testTerminalRequestIsNeverSaved(AccountCategoryChangeRequestStatus $status): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $request = $this->request($accountId, $status);
        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($request);
        $repository->shouldNotReceive('save');
        $this->expectException(InvalidAccountCategoryChangeRequestWithdrawalException::class);

        try {
            (new WithdrawAccountCategoryChangeRequest($repository))->process(new WithdrawAccountCategoryChangeRequestInput($accountId, $request->requestIdentifier(), $this->principal($accountId)), new WithdrawAccountCategoryChangeRequestOutput());
        } finally {
            $this->assertSame($status, $request->status());
        }
    }

    #[Group('useDb')]
    public function testConcreteRepositoryWithdrawalKeepsAccountCategoryAndHistory(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountId, ['category' => 'talent']);
        $repository = new AccountCategoryChangeRequestRepository();
        $request = $this->request($accountId, AccountCategoryChangeRequestStatus::PENDING);
        $repository->save($request);
        $output = new WithdrawAccountCategoryChangeRequestOutput();
        (new WithdrawAccountCategoryChangeRequest($repository))->process(new WithdrawAccountCategoryChangeRequestInput($accountId, $request->requestIdentifier(), $this->principal($accountId)), $output);
        $found = $repository->findById($request->requestIdentifier());
        $this->assertNotNull($found);
        $this->assertSame(AccountCategoryChangeRequestStatus::WITHDRAWN, $found->status());
        $this->assertDatabaseHas('accounts', ['id' => (string) $accountId, 'category' => 'talent']);
        $this->assertDatabaseCount('account_category_change_requests', 1);
        $payload = $output->toArray();
        $this->assertArrayHasKey('currentAccountCategory', $payload);
        $this->assertSame('general', $payload['currentAccountCategory'] ?? null);
        $this->assertNull($found->reviewedBy());
        $this->assertNull($found->reviewedAt());
    }

    private function request(AccountIdentifier $accountId, AccountCategoryChangeRequestStatus $status): AccountCategoryChangeRequest
    {
        return new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, $status, new DateTimeImmutable(), null, null, null, CreateAccountCategoryChangeRequestAccount::create());
    }

    private function principal(AccountIdentifier $accountId): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountId);
    }
}
