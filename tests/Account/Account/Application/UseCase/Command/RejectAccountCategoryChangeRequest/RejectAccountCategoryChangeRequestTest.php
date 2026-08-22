<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequest;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestRejectionException;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectAccountCategoryChangeRequestTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(AccountCategoryChangeRequestRepositoryInterface::class, Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(RejectAccountCategoryChangeRequest::class, $this->app->make(\Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestInterface::class));
    }

    public function testRejectUpdatesRequestOnlyWhenOperationsPolicyAllows(): void
    {
        $requestId = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        $targetAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($reviewerAccountId);
        $request = $this->request($requestId, $targetAccountId, AccountCategoryChangeRequestStatus::PENDING);
        $rejectionReason = new RejectionReason(RejectionReasonCode::OTHER, 'category evidence is insufficient');

        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->with($requestId)->andReturn($request);
        $requestRepository->shouldReceive('save')->once()->with($request);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE, Mockery::on(static fn (Resource $resource): bool => (string) $resource->accountIdentifier() === (string) $reviewerAccountId))
            ->andReturnTrue();

        $output = new RejectAccountCategoryChangeRequestOutput();
        (new RejectAccountCategoryChangeRequest($requestRepository, $policyEvaluator))
            ->process(new RejectAccountCategoryChangeRequestInput($requestId, $principal, $rejectionReason), $output);

        $this->assertSame(AccountCategoryChangeRequestStatus::REJECTED, $request->status());
        $this->assertSame((string) $reviewerAccountId, (string) $request->reviewedBy());
        $this->assertSame($rejectionReason, $request->rejectionReason());
        $this->assertSame(AccountCategory::GENERAL, $request->currentAccountCategory());
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

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountCategoryChangeRequestForbiddenException::class);
        (new RejectAccountCategoryChangeRequest($requestRepository, $policyEvaluator))
            ->process(new RejectAccountCategoryChangeRequestInput($requestId, $principal, new RejectionReason(RejectionReasonCode::OTHER, 'missing information')), new RejectAccountCategoryChangeRequestOutput());
    }

    public function testNotFoundWhenRequestDoesNotExist(): void
    {
        $requestId = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->with($requestId)->andReturnNull();

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldNotReceive('evaluate');

        $this->expectException(AccountCategoryChangeRequestNotFoundException::class);
        (new RejectAccountCategoryChangeRequest($requestRepository, $policyEvaluator))
            ->process(new RejectAccountCategoryChangeRequestInput($requestId, $this->principal(new AccountIdentifier(StrTestHelper::generateUuid())), new RejectionReason(RejectionReasonCode::OTHER, 'missing information')), new RejectAccountCategoryChangeRequestOutput());
    }

    public function testCannotRejectNonPendingRequest(): void
    {
        $requestId = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        $request = $this->request($requestId, new AccountIdentifier(StrTestHelper::generateUuid()), AccountCategoryChangeRequestStatus::APPROVED);

        /** @var AccountCategoryChangeRequestRepositoryInterface&Mockery\MockInterface $requestRepository */
        $requestRepository = Mockery::mock(AccountCategoryChangeRequestRepositoryInterface::class);
        $requestRepository->shouldReceive('findById')->once()->andReturn($request);
        $requestRepository->shouldNotReceive('save');

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();

        $this->expectException(InvalidAccountCategoryChangeRequestRejectionException::class);
        (new RejectAccountCategoryChangeRequest($requestRepository, $policyEvaluator))
            ->process(new RejectAccountCategoryChangeRequestInput($requestId, $this->principal(new AccountIdentifier(StrTestHelper::generateUuid())), new RejectionReason(RejectionReasonCode::OTHER, 'missing information')), new RejectAccountCategoryChangeRequestOutput());
    }

    private function request(AccountCategoryChangeRequestIdentifier $requestId, AccountIdentifier $accountId, AccountCategoryChangeRequestStatus $status): AccountCategoryChangeRequest
    {
        return new AccountCategoryChangeRequest($requestId, $accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, $status, new DateTimeImmutable(), null, null, null);
    }

    private function principal(AccountIdentifier $accountId): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountId);
    }
}
