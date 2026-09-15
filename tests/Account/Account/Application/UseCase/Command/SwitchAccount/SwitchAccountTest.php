<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\SwitchAccount;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccount;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInput;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SwitchAccountTest extends TestCase
{
    public function testSwitchesToDelegatedAccountByReusingExistingPrincipal(): void
    {
        [$useCase, $input, $output, $deps, $targetPrincipal] = $this->scenario(existingPrincipal: true);
        $deps->principalFactory->shouldNotReceive('create');
        $deps->principalRepository->shouldNotReceive('save');
        $deps->principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $deps->currentAccountService->shouldReceive('save')->once()->withArgs(
            fn (CurrentAccount $currentAccount): bool =>
                (string) $currentAccount->effectivePrincipalIdentifier === (string) $targetPrincipal->principalIdentifier()
                && (string) $currentAccount->originalIdentityIdentifier === (string) $input->identityIdentifier()
                && $currentAccount->delegationIdentifier !== null,
        );

        $useCase->process($input, $output);

        $this->assertSame((string) $targetPrincipal->principalIdentifier(), (string) $output->currentAccount()->effectivePrincipalIdentifier);
    }

    public function testSwitchesFromOneDelegatedAccountToAnotherUsingOriginalPrincipal(): void
    {
        [$useCase, $input, $output, $deps, $targetPrincipal, $targetAccount] = $this->scenario(
            existingPrincipal: true,
        );
        $deps->policy->shouldReceive('evaluate')->once()->withArgs(
            fn (Principal $principal): bool => (string) $principal->accountIdentifier() !== (string) $targetAccount,
        )->andReturnTrue();
        $deps->currentAccountService->shouldReceive('save')->once();

        $useCase->process($input, $output);

        $this->assertSame((string) $targetPrincipal->principalIdentifier(), (string) $output->currentAccount()->effectivePrincipalIdentifier);
    }

    public function testSwitchesBackToOriginalAccountWithSameUseCase(): void
    {
        [$useCase, $input, $output, $deps, , , $originalPrincipal] = $this->scenario(
            existingPrincipal: true,
            switchBack: true,
        );
        $deps->delegationRepository->shouldNotReceive('findById');
        $deps->accountRepository->shouldNotReceive('findById');
        $deps->policy->shouldNotReceive('evaluate');
        $deps->currentAccountService->shouldReceive('save')->once()->withArgs(
            fn (CurrentAccount $currentAccount): bool =>
                $currentAccount->delegationIdentifier === null
                && (string) $currentAccount->effectivePrincipalIdentifier === (string) $originalPrincipal->principalIdentifier(),
        );

        $useCase->process($input, $output);

        $this->assertNull($output->currentAccount()->delegationIdentifier);
        $this->assertSame((string) $originalPrincipal->principalIdentifier(), (string) $output->currentAccount()->effectivePrincipalIdentifier);
    }

    public function testCreatesMissingPrincipalWithDefaultGroupMembershipOnly(): void
    {
        [$useCase, $input, $output, $deps, $targetPrincipal, $targetAccount] = $this->scenario(existingPrincipal: false);
        $defaultGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $targetAccount,
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $deps->principalFactory->shouldReceive('create')
            ->once()->with($input->identityIdentifier(), $targetAccount)->andReturn($targetPrincipal);
        $deps->principalRepository->shouldReceive('save')->once()->with($targetPrincipal);
        $deps->principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->once()->with($targetAccount)->andReturn($defaultGroup);
        $deps->principalGroupRepository->shouldReceive('save')->once()->with($defaultGroup);
        $deps->currentAccountService->shouldReceive('save')->once();

        $useCase->process($input, $output);

        $this->assertTrue($defaultGroup->hasMember($targetPrincipal->principalIdentifier()));
        $this->assertTrue($defaultGroup->isDefault());
    }

    #[DataProvider('inactiveStatuses')]
    public function testRejectsInactiveDelegation(DelegationStatus $status): void
    {
        [$useCase, $input, $output, $deps] = $this->scenario(existingPrincipal: true, status: $status);
        $deps->accountRepository->shouldNotReceive('findById');
        $deps->currentAccountService->shouldNotReceive('save');

        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process($input, $output);
    }

    /** @return array<string, array{DelegationStatus}> */
    public static function inactiveStatuses(): array
    {
        return ['pending' => [DelegationStatus::PENDING], 'rejected' => [DelegationStatus::REJECTED]];
    }

    public function testRejectsDeletedDelegation(): void
    {
        [$useCase, $input, $output, $deps] = $this->scenario(existingPrincipal: true);
        $deps->delegationRepository->shouldReceive('findById')->andReturn(null)->byDefault();

        $this->expectException(DelegationNotFoundException::class);
        $useCase->process($input, $output);
    }

    public function testRejectsWhenOriginalPrincipalLacksSwitchPermission(): void
    {
        [$useCase, $input, $output, $deps] = $this->scenario(existingPrincipal: true);
        $deps->policy->shouldReceive('evaluate')->andReturn(false)->byDefault();
        $deps->currentAccountService->shouldNotReceive('save');

        $this->expectException(DisallowedDelegationOperationException::class);
        $useCase->process($input, $output);
    }

    /**
     * @return array{
     *     SwitchAccount,
     *     SwitchAccountInput,
     *     SwitchAccountOutput,
     *     object,
     *     Principal,
     *     AccountIdentifier,
     *     Principal
     * }
     */
    private function scenario(
        bool $existingPrincipal,
        DelegationStatus $status = DelegationStatus::APPROVED,
        ?DelegationIdentifier $targetDelegation = null,
        bool $switchBack = false,
    ): array {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegateAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $targetAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $originalPrincipal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, $delegateAccountId);
        $targetPrincipal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, $targetAccountId);
        $delegation = new Delegation(
            $targetDelegation ?? new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $delegateAccountId,
            $targetAccountId,
            $delegateAccountId,
            $status,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            $status === DelegationStatus::APPROVED ? new DateTimeImmutable() : null,
            $status === DelegationStatus::REJECTED ? new DateTimeImmutable() : null,
        );
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var DelegationRepositoryInterface&Mockery\MockInterface $delegationRepository */
        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var PrincipalFactoryInterface&Mockery\MockInterface $principalFactory */
        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policy */
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        /** @var CurrentAccountServiceInterface&Mockery\MockInterface $currentAccountService */
        $currentAccountService = Mockery::mock(CurrentAccountServiceInterface::class);
        $delegationRepository->shouldReceive('findById')->with($delegation->delegationIdentifier())->andReturn($delegation)->byDefault();
        $accountRepository->shouldReceive('findById')->with($delegateAccountId)->andReturn($this->account($delegateAccountId))->byDefault();
        $principalRepository->shouldReceive('findById')->with($originalPrincipal->principalIdentifier())->andReturn($originalPrincipal)->byDefault();
        $policy->shouldReceive('evaluate')->andReturn(true)->byDefault();
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->with($identity, $targetAccountId)->andReturn($existingPrincipal ? $targetPrincipal : null)->byDefault();
        $deps = (object) compact('accountRepository', 'delegationRepository', 'principalRepository', 'principalFactory', 'principalGroupRepository', 'policy', 'currentAccountService');
        $useCase = new SwitchAccount($accountRepository, $delegationRepository, $principalRepository, $principalFactory, $principalGroupRepository, $policy, $currentAccountService);

        return [
            $useCase,
            new SwitchAccountInput($identity, $originalPrincipal->principalIdentifier(), $switchBack ? null : $delegation->delegationIdentifier()),
            new SwitchAccountOutput(),
            $deps,
            $targetPrincipal,
            $targetAccountId,
            $originalPrincipal,
        ];
    }

    private function account(AccountIdentifier $id): Account
    {
        return new Account(
            $id,
            new Email('account@example.com'),
            AccountType::CORPORATION,
            new AccountName('Account'),
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments([]),
        );
    }
}
