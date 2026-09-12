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
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Account\Delegation\Domain\Repository\AccountDelegationRepositoryInterface;
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
    public function testDelegatorAccountWithPolicyCanRejectPendingRequest(): void
    {
        [$useCase, $input] = $this->scenario(true, true);
        $useCase->process($input);
        $this->addToAssertionCount(1);
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

    /** @return array{RejectDelegation, RejectDelegationInput, AccountDelegation} */
    private function scenario(bool $allowed, bool $pending, bool $expectsAuthorization = true): array
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal($talent);
        $delegation = new AccountDelegation(new DelegationIdentifier(StrTestHelper::generateUuid()), new AffiliationIdentifier(StrTestHelper::generateUuid()), $agency, $talent, $agency, $pending ? DelegationStatus::PENDING : DelegationStatus::APPROVED, DelegationDirection::FROM_AGENCY, new DateTimeImmutable(), $pending ? null : new DateTimeImmutable(), null);
        /** @var AccountDelegationRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(AccountDelegationRepositoryInterface::class);
        $repository->shouldReceive('findById')->andReturn($delegation);
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accounts */
        $accounts = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policy */
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        if ($pending && $expectsAuthorization) {
            $account = Mockery::mock(Account::class);
            $account->shouldReceive('accountIdentifier')->andReturn($talent);
            $account->shouldReceive('type')->andReturn(AccountType::INDIVIDUAL);
            $account->shouldReceive('accountCategory')->andReturn(AccountCategory::TALENT);
            $accounts->shouldReceive('findById')->with($talent)->andReturn($account);
            $policy->shouldReceive('evaluate')->with($principal, Action::DELEGATION_REJECT, Mockery::any())->andReturn($allowed);
            if ($allowed) {
                $repository->shouldReceive('delete')->with($delegation)->once();
            }
        }

        return [new RejectDelegation($accounts, $repository, $policy), new RejectDelegationInput($delegation->delegationIdentifier(), $principal), $delegation];
    }

    private function principal(AccountIdentifier $account): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account);
    }
}
