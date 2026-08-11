<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequest;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestApprovalException;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
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

class ApproveAccountCategoryChangeRequestTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(AccountCategoryChangeRequestRepositoryInterface::class, Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class));
        $this->app->instance(AccountRepositoryInterface::class, Mockery::mock(AccountRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(ApproveAccountCategoryChangeRequest::class, $this->app->make(\Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestInterface::class));
    }

    public function testApproveUpdatesRequestAndAccountCategoryWhenOperationsPolicyAllows(): void
    {
        $requestId = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        $targetAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($reviewerAccountId);
        $request = $this->request($requestId, $targetAccountId, AccountCategoryChangeRequestStatus::PENDING);
        $account = $this->account($targetAccountId, AccountType::INDIVIDUAL);

        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->with($requestId)->andReturn($request);
        $requestRepository->shouldReceive('save')->once()->with($request);

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->once()->with($targetAccountId)->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with(Mockery::on(static fn (Account $saved): bool => $saved->accountCategory() === AccountCategory::AGENCY));

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE, Mockery::on(static fn (Resource $resource): bool => (string) $resource->accountIdentifier() === (string) $reviewerAccountId))
            ->andReturnTrue();

        $output = new ApproveAccountCategoryChangeRequestOutput();
        (new ApproveAccountCategoryChangeRequest($requestRepository, $accountRepository, $policyEvaluator))
            ->process(new ApproveAccountCategoryChangeRequestInput($requestId, $principal), $output);

        $this->assertSame(AccountCategoryChangeRequestStatus::APPROVED, $request->status());
        $this->assertSame((string) $reviewerAccountId, (string) $request->reviewedBy());
        $this->assertSame(AccountCategory::AGENCY, $account->accountCategory());
    }

    public function testForbiddenWhenReviewerDoesNotHaveOperationsPolicy(): void
    {
        $requestId = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $request = $this->request($requestId, new AccountIdentifier(StrTestHelper::generateUuid()), AccountCategoryChangeRequestStatus::PENDING);

        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->andReturn($request);
        $requestRepository->shouldNotReceive('save');

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldNotReceive('findById');
        $accountRepository->shouldNotReceive('save');

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountCategoryChangeRequestForbiddenException::class);
        (new ApproveAccountCategoryChangeRequest($requestRepository, $accountRepository, $policyEvaluator))
            ->process(new ApproveAccountCategoryChangeRequestInput($requestId, $principal), new ApproveAccountCategoryChangeRequestOutput());
    }

    public function testNotFoundWhenRequestDoesNotExist(): void
    {
        $requestId = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->with($requestId)->andReturnNull();

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);

        $this->expectException(AccountCategoryChangeRequestNotFoundException::class);
        (new ApproveAccountCategoryChangeRequest(
            $requestRepository,
            $accountRepository,
            $policyEvaluator,
        ))->process(new ApproveAccountCategoryChangeRequestInput($requestId, $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()))), new ApproveAccountCategoryChangeRequestOutput());
    }

    public function testCannotApproveNonPendingRequest(): void
    {
        $requestId = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        $targetAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $request = $this->request($requestId, $targetAccountId, AccountCategoryChangeRequestStatus::APPROVED);

        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->andReturn($request);
        $requestRepository->shouldNotReceive('save');

        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->once()->with($targetAccountId)->andReturn($this->account($targetAccountId, AccountType::INDIVIDUAL));
        $accountRepository->shouldNotReceive('save');

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();

        $this->expectException(InvalidAccountCategoryChangeRequestApprovalException::class);
        (new ApproveAccountCategoryChangeRequest($requestRepository, $accountRepository, $policyEvaluator))
            ->process(new ApproveAccountCategoryChangeRequestInput($requestId, $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()))), new ApproveAccountCategoryChangeRequestOutput());
    }

    private function request(AccountCategoryChangeRequestIdentifier $requestId, AccountIdentifier $accountId, AccountCategoryChangeRequestStatus $status): AccountCategoryChangeRequest
    {
        return new AccountCategoryChangeRequest($requestId, $accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, $status, new DateTimeImmutable(), null, null, null);
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
