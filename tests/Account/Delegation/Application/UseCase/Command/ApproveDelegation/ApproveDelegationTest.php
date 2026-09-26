<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegation;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInput;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationOutput;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Delegation\Domain\Service\DelegationPrincipalGroupServiceInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveDelegationTest extends TestCase
{
    public function testAccountThatDidNotRequestCanApproveAndCreatesPrincipalGroup(): void
    {
        [$useCase, $input, $output, $delegation] = $this->scenario(true, true);
        $useCase->process($input, $output);
        $this->assertSame(DelegationStatus::APPROVED, $delegation->status());
        $this->assertSame('approved', $output->toArray()['status']);
    }

    public function testAgencyCanApproveRequestSentByTalent(): void
    {
        [$useCase, $input, $output, $delegation] = $this->scenario(true, true, requestedByAgency: false);
        $useCase->process($input, $output);
        $this->assertSame(DelegationStatus::APPROVED, $delegation->status());
        $this->assertSame('approved', $output->toArray()['status']);
    }

    public function testPrincipalFromAnotherAccountCannotApprove(): void
    {
        [$useCase, , $output, $delegation] = $this->scenario(true, true, false);
        $other = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process(new ApproveDelegationInput($delegation->delegationIdentifier(), $other), $output);
    }

    public function testPolicyDeniedCannotApprove(): void
    {
        [$useCase, $input, $output] = $this->scenario(false, true);
        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process($input, $output);
    }

    public function testNonPendingDelegationCannotApprove(): void
    {
        [$useCase, $input, $output] = $this->scenario(true, false);
        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process($input, $output);
    }

    /** @return array{ApproveDelegation, ApproveDelegationInput, ApproveDelegationOutput, Delegation} */
    private function scenario(bool $allowed, bool $pending, bool $expectsAuthorization = true, bool $requestedByAgency = true): array
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $requestedByAgency ? $agency : $talent;
        $approver = $requestedByAgency ? $talent : $agency;
        $principal = $this->principal($approver);
        $delegation = new Delegation(new DelegationIdentifier(StrTestHelper::generateUuid()), new AffiliationIdentifier(StrTestHelper::generateUuid()), $agency, $talent, $requestedBy, $pending ? DelegationStatus::PENDING : DelegationStatus::APPROVED, $requestedByAgency ? DelegationDirection::FROM_AGENCY : DelegationDirection::FROM_TALENT, new DateTimeImmutable(), $pending ? null : new DateTimeImmutable(), null);
        /** @var DelegationRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(DelegationRepositoryInterface::class);
        $repository->shouldReceive('findById')->andReturn($delegation);
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accounts */
        $accounts = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policy */
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        /** @var DelegationPrincipalGroupServiceInterface&Mockery\MockInterface $groups */
        $groups = Mockery::mock(DelegationPrincipalGroupServiceInterface::class);
        if ($pending && $expectsAuthorization) {
            $account = Mockery::mock(Account::class);
            $account->shouldReceive('accountIdentifier')->andReturn($approver);
            $account->shouldReceive('type')->andReturn($requestedByAgency ? AccountType::INDIVIDUAL : AccountType::CORPORATION);
            $account->shouldReceive('accountCategory')->andReturn($requestedByAgency ? AccountCategory::TALENT : AccountCategory::AGENCY);
            $accounts->shouldReceive('findById')->with($approver)->andReturn($account);
            $policy->shouldReceive('evaluate')->with($principal, Action::DELEGATION_APPROVE, Mockery::any())->andReturn($allowed);
            if ($allowed) {
                $groups->shouldReceive('createFor')->with($delegation)->once();
                $repository->shouldReceive('save')->with($delegation)->once();
            }
        }

        return [new ApproveDelegation($accounts, $repository, $policy, $groups), new ApproveDelegationInput($delegation->delegationIdentifier(), $principal), new ApproveDelegationOutput(), $delegation];
    }

    private function principal(AccountIdentifier $account): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account);
    }
}
