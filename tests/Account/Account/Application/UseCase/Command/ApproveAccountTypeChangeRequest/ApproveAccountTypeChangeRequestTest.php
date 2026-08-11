<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountTypeChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest\ApproveAccountTypeChangeRequest;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest\ApproveAccountTypeChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest\ApproveAccountTypeChangeRequestOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\Exception\InvalidAccountTypeChangeRequestApprovalException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountTypeChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAccountTypeChangeRequestTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(AccountTypeChangeRequestRepositoryInterface::class, Mockery::mock(AccountTypeChangeRequestRepositoryInterface::class));
        $this->app->instance(AccountRepositoryInterface::class, Mockery::mock(AccountRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(ApproveAccountTypeChangeRequest::class, $this->app->make(\Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest\ApproveAccountTypeChangeRequestInterface::class));
    }

    public function testApproveUpdatesRequestAndAccountTypeWhenOperationsPolicyAllows(): void
    {
        $requestId = new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid());
        $targetAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($reviewerAccountId);
        $request = $this->request($requestId, $targetAccountId, AccountTypeChangeRequestStatus::PENDING);
        $account = $this->account($targetAccountId, AccountType::INDIVIDUAL);

        /** @var AccountTypeChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountTypeChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->with($requestId)->andReturn($request);
        $requestRepository->shouldReceive('save')->once()->with($request);

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->once()->with($targetAccountId)->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with(Mockery::on(static fn (Account $saved): bool => $saved->type() === AccountType::CORPORATION));

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::ACCOUNT_TYPE_CHANGE_REQUEST_APPROVE, Mockery::on(static fn (Resource $resource): bool => (string) $resource->accountIdentifier() === (string) $reviewerAccountId))
            ->andReturnTrue();

        $output = new ApproveAccountTypeChangeRequestOutput();
        (new ApproveAccountTypeChangeRequest($requestRepository, $accountRepository, $policyEvaluator))
            ->process(new ApproveAccountTypeChangeRequestInput($requestId, $principal), $output);

        $this->assertSame(AccountTypeChangeRequestStatus::APPROVED, $request->status());
        $this->assertSame((string) $reviewerAccountId, (string) $request->reviewedBy());
        $this->assertSame(AccountType::CORPORATION, $account->type());
    }

    public function testForbiddenWhenReviewerDoesNotHaveOperationsPolicy(): void
    {
        $requestId = new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $request = $this->request($requestId, new AccountIdentifier(StrTestHelper::generateUuid()), AccountTypeChangeRequestStatus::PENDING);

        /** @var AccountTypeChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountTypeChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->andReturn($request);
        $requestRepository->shouldNotReceive('save');

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldNotReceive('findById');
        $accountRepository->shouldNotReceive('save');

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountTypeChangeRequestForbiddenException::class);
        (new ApproveAccountTypeChangeRequest($requestRepository, $accountRepository, $policyEvaluator))
            ->process(new ApproveAccountTypeChangeRequestInput($requestId, $principal), new ApproveAccountTypeChangeRequestOutput());
    }

    public function testNotFoundWhenRequestDoesNotExist(): void
    {
        $requestId = new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid());
        /** @var AccountTypeChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountTypeChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->with($requestId)->andReturnNull();

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);

        $this->expectException(AccountTypeChangeRequestNotFoundException::class);
        (new ApproveAccountTypeChangeRequest(
            $requestRepository,
            $accountRepository,
            $policyEvaluator,
        ))->process(new ApproveAccountTypeChangeRequestInput($requestId, $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()))), new ApproveAccountTypeChangeRequestOutput());
    }

    public function testCannotApproveNonPendingRequest(): void
    {
        $requestId = new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid());
        $targetAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $request = $this->request($requestId, $targetAccountId, AccountTypeChangeRequestStatus::APPROVED);

        /** @var AccountTypeChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountTypeChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->andReturn($request);
        $requestRepository->shouldNotReceive('save');

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->once()->with($targetAccountId)->andReturn($this->account($targetAccountId, AccountType::INDIVIDUAL));
        $accountRepository->shouldNotReceive('save');

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();

        $this->expectException(InvalidAccountTypeChangeRequestApprovalException::class);
        (new ApproveAccountTypeChangeRequest($requestRepository, $accountRepository, $policyEvaluator))
            ->process(new ApproveAccountTypeChangeRequestInput($requestId, $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()))), new ApproveAccountTypeChangeRequestOutput());
    }

    private function request(AccountTypeChangeRequestIdentifier $requestId, AccountIdentifier $accountId, AccountTypeChangeRequestStatus $status): AccountTypeChangeRequest
    {
        return new AccountTypeChangeRequest($requestId, $accountId, AccountType::INDIVIDUAL, AccountType::CORPORATION, $status, new DateTimeImmutable(), null, null, null);
    }

    private function account(AccountIdentifier $accountId, AccountType $type): Account
    {
        return new Account($accountId, new Email('account@example.com'), $type, new AccountName('Account'), AccountStatus::ACTIVE, AccountCategory::GENERAL, DeletionReadinessChecklist::ready(), new AccountDocuments([]));
    }

    private function principal(AccountIdentifier $accountId): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountId);
    }
}
