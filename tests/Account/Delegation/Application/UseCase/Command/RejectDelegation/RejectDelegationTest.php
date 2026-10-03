<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\RejectDelegation;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Command\RejectDelegation\RejectDelegation;
use Source\Account\Delegation\Application\UseCase\Command\RejectDelegation\RejectDelegationInput;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
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

class RejectDelegationTest extends TestCase
{
    public function testAccountThatDidNotRequestCanRejectPendingRequest(): void
    {
        [$useCase, $input, $delegation] = $this->scenario(true, true);
        $useCase->process($input);
        $this->assertSame(DelegationStatus::REJECTED, $delegation->status());
        $this->assertNotNull($delegation->rejectedAt());
    }

    public function testAgencyCanRejectRequestSentByTalent(): void
    {
        [$useCase, $input, $delegation] = $this->scenario(true, true, requestedByAgency: false);
        $useCase->process($input);
        $this->assertSame(DelegationStatus::REJECTED, $delegation->status());
        $this->assertNotNull($delegation->rejectedAt());
    }

    public function testPolicyDeniedCannotReject(): void
    {
        [$useCase, $input] = $this->scenario(false, true);
        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process($input);
    }

    public function testNonPendingDelegationCannotReject(): void
    {
        [$useCase, $input] = $this->scenario(true, false);
        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process($input);
    }

    public function testPrincipalFromAnotherAccountCannotReject(): void
    {
        [$useCase, , $delegation] = $this->scenario(true, true, false);
        $other = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process(new RejectDelegationInput($delegation->delegationIdentifier(), $other));
    }

    /** @return array{RejectDelegation, RejectDelegationInput, Delegation} */
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
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policy */
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        if ($pending && $expectsAuthorization) {
            $account = Mockery::mock(Account::class);
            $account->shouldReceive('accountIdentifier')->andReturn($approver);
            $account->shouldReceive('type')->andReturn($requestedByAgency ? AccountType::INDIVIDUAL : AccountType::CORPORATION);
            $account->shouldReceive('accountCategory')->andReturn($requestedByAgency ? AccountCategory::TALENT : AccountCategory::AGENCY);
            $accountRepository->shouldReceive('findById')->with($approver)->andReturn($account);
            $policy->shouldReceive('evaluate')->with($principal, Action::DELEGATION_REJECT, Mockery::any())->andReturn($allowed);
            if ($allowed) {
                $repository->shouldReceive('save')->with($delegation)->once();
            }
        }

        return [new RejectDelegation($accountRepository, $repository, $policy), new RejectDelegationInput($delegation->delegationIdentifier(), $principal), $delegation];
    }

    private function principal(AccountIdentifier $account): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account);
    }
}
