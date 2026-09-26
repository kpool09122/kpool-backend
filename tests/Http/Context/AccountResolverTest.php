<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\AccountResolver;
use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountResolverTest extends TestCase
{
    public function testResolvesExplicitDelegatedContextAndKeepsOriginalIdentityForAudit(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $effectiveAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $originalPrincipal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $effectivePrincipal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $selected = new CurrentAccount($identity, $originalAccount, $originalPrincipal, $effectiveAccount, $effectivePrincipal, $delegationId);
        $principal = new Principal($effectivePrincipal, $identity, $effectiveAccount);
        $delegation = $this->delegation($delegationId, $originalAccount, $effectiveAccount, DelegationStatus::APPROVED);
        [$resolver, $deps] = $this->resolver();
        $deps['currentAccountService']->shouldReceive('find')->with($identity)->once()->andReturn($selected);
        $deps['principals']->shouldReceive('findById')->with($effectivePrincipal)->once()->andReturn($principal);
        $deps['delegations']->shouldReceive('findById')->with($delegationId)->once()->andReturn($delegation);
        $deps['accounts']->shouldReceive('findById')->with($effectiveAccount)->once()->andReturn($this->account($effectiveAccount));
        $deps['groups']->shouldReceive('findByAccountIdAndPrincipal')->once()->andReturn([]);

        $currentAccount = $resolver->resolve($identity);

        $this->assertSame($effectiveAccount, $currentAccount->principal()->accountIdentifier());
        $this->assertSame($identity, $currentAccount->originalIdentityIdentifier());
        $this->assertSame($delegationId, $currentAccount->delegationIdentifier());
    }

    public function testRejectsStoredContextWhenDelegationIsNoLongerActive(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $effectiveAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $effectivePrincipal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $selected = new CurrentAccount($identity, $originalAccount, new PrincipalIdentifier(StrTestHelper::generateUuid()), $effectiveAccount, $effectivePrincipal, $delegationId);
        [$resolver, $deps] = $this->resolver();
        $deps['currentAccountService']->shouldReceive('find')->andReturn($selected);
        $deps['principals']->shouldReceive('findById')->andReturn(new Principal($effectivePrincipal, $identity, $effectiveAccount));
        $deps['delegations']->shouldReceive('findById')->andReturn($this->delegation($delegationId, $originalAccount, $effectiveAccount, DelegationStatus::REJECTED));
        $deps['currentAccountService']->shouldReceive('save')->once()->withArgs(
            fn (CurrentAccount $currentAccount): bool => $currentAccount->delegationIdentifier === null
                && (string) $currentAccount->effectiveAccountIdentifier === (string) $originalAccount,
        );

        $this->expectException(DelegationUnavailableException::class);
        $resolver->resolve($identity);
    }

    public function testResolvesOriginalPrincipalWhenDelegatedPrincipalRemainsAfterSessionReset(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $effectiveAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $originalPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $effectivePrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $originalPrincipal = new Principal($originalPrincipalId, $identity, $originalAccount);
        $effectivePrincipal = new Principal($effectivePrincipalId, $identity, $effectiveAccount);
        [$resolver, $deps] = $this->resolver();
        $deps['currentAccountService']->shouldReceive('find')->with($identity)->once()->andReturn(null);
        $deps['principals']->shouldReceive('findAllByIdentityIdentifier')->with($identity)->once()->andReturn([
            $originalPrincipal,
            $effectivePrincipal,
        ]);
        $deps['delegations']->shouldReceive('findApprovedBetweenAccountIds')->once()->withArgs(
            fn (array $accountIdentifiers): bool => array_map('strval', $accountIdentifiers) === [
                (string) $originalAccount,
                (string) $effectiveAccount,
            ],
        )->andReturn([$this->delegation($delegationId, $originalAccount, $effectiveAccount, DelegationStatus::APPROVED)]);
        $deps['currentAccountService']->shouldReceive('save')->once()->withArgs(
            fn (CurrentAccount $currentAccount): bool => $currentAccount->delegationIdentifier === null
                && (string) $currentAccount->originalAccountIdentifier === (string) $originalAccount
                && (string) $currentAccount->effectiveAccountIdentifier === (string) $originalAccount,
        );
        $deps['principals']->shouldReceive('findById')->with($originalPrincipalId)->once()->andReturn($originalPrincipal);
        $deps['accounts']->shouldReceive('findById')->with($originalAccount)->once()->andReturn($this->account($originalAccount));
        $deps['groups']->shouldReceive('findByAccountIdAndPrincipal')->once()->andReturn([]);

        $currentAccount = $resolver->resolve($identity);

        $this->assertSame($originalAccount, $currentAccount->principal()->accountIdentifier());
        $this->assertNull($currentAccount->delegationIdentifier());
    }

    public function testDoesNotUseAmbiguousIdentityOnlyFallback(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        [$resolver, $deps] = $this->resolver();
        $deps['currentAccountService']->shouldReceive('find')->andReturn(null);
        $deps['principals']->shouldReceive('findAllByIdentityIdentifier')->with($identity)->once()->andReturn([
            new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, new AccountIdentifier(StrTestHelper::generateUuid())),
            new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, new AccountIdentifier(StrTestHelper::generateUuid())),
        ]);
        $deps['delegations']->shouldReceive('findApprovedBetweenAccountIds')->once()->andReturn([]);
        $deps['principals']->shouldNotReceive('findByIdentityIdentifier');

        $this->expectException(AccountNotFoundException::class);
        $resolver->resolve($identity);
    }

    /** @return array{AccountResolver, array<string, Mockery\MockInterface>} */
    private function resolver(): array
    {
        /** @var CurrentAccountServiceInterface&Mockery\MockInterface $currentAccountService */
        $currentAccountService = Mockery::mock(CurrentAccountServiceInterface::class);
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accounts */
        $accounts = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principals */
        $principals = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var DelegationRepositoryInterface&Mockery\MockInterface $delegations */
        $delegations = Mockery::mock(DelegationRepositoryInterface::class);
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $groups */
        $groups = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        /** @var RoleRepositoryInterface&Mockery\MockInterface $roles */
        $roles = Mockery::mock(RoleRepositoryInterface::class);
        /** @var PolicyRepositoryInterface&Mockery\MockInterface $policies */
        $policies = Mockery::mock(PolicyRepositoryInterface::class);

        return [new AccountResolver($currentAccountService, $accounts, $principals, $delegations, $groups, $roles, $policies), compact('currentAccountService', 'accounts', 'principals', 'delegations', 'groups', 'roles', 'policies')];
    }

    private function delegation(DelegationIdentifier $id, AccountIdentifier $delegate, AccountIdentifier $delegator, DelegationStatus $status): Delegation
    {
        return new Delegation($id, new AffiliationIdentifier(StrTestHelper::generateUuid()), $delegate, $delegator, $delegate, $status, DelegationDirection::FROM_AGENCY, new DateTimeImmutable(), $status === DelegationStatus::APPROVED ? new DateTimeImmutable() : null, $status === DelegationStatus::REJECTED ? new DateTimeImmutable() : null);
    }

    private function account(AccountIdentifier $id): Account
    {
        return new Account($id, new Email('account@example.com'), AccountType::CORPORATION, new AccountName('Account'), AccountStatus::ACTIVE, AccountCategory::AGENCY, DeletionReadinessChecklist::ready(), new AccountDocuments([]));
    }
}
