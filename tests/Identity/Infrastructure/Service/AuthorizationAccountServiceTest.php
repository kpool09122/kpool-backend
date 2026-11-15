<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Account\Delegation;
use Application\Models\Account\Principal;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Principal\Domain\Entity\Principal as PrincipalEntity;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationAccountServiceTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function delegationStates(): array
    {
        return ['approved' => ['approved'], 'policy denied' => ['denied'], 'pending delegation' => ['pending'], 'unrelated delegation' => ['foreign'], 'inactive target' => ['inactive'], 'inactive effective principal' => ['principal']];
    }

    #[DataProvider('delegationStates')]
    public function testChecksSelectedDelegatedTargetAndRechecksRevocation(string $case): void
    {
        $identity = new IdentityIdentifier((string) Str::uuid7());
        CreateIdentity::create($identity);
        $original = new AccountIdentifier((string) Str::uuid7());
        $target = new AccountIdentifier((string) Str::uuid7());
        CreateAccount::create((string) $original);
        CreateAccount::create((string) $target, ['status' => $case === 'inactive' ? 'suspended' : 'active']);
        $principal = new PrincipalIdentifier((string) Str::uuid7());
        Principal::query()->create(['id' => (string) $principal, 'identity_id' => (string) $identity, 'account_id' => (string) $original, 'status' => 'active']);
        app(CurrentAccountServiceInterface::class)->save(new CurrentAccount($identity, $original, $principal, $original, $principal, null));
        $delegation = Delegation::query()->create([
            'id' => (string) Str::uuid7(), 'affiliation_id' => (string) Str::uuid7(),
            'delegate_account_id' => $case === 'foreign' ? (string) Str::uuid7() : (string) $original,
            'delegator_account_id' => (string) $target, 'requested_by_account_id' => (string) $target,
            'status' => $case === 'pending' ? 'pending' : 'approved', 'direction' => DelegationDirection::FROM_AGENCY->value, 'requested_at' => now(),
        ]);
        if ($case === 'principal') {
            Principal::query()->create(['id' => (string) Str::uuid7(), 'identity_id' => (string) $identity, 'account_id' => (string) $target, 'status' => 'suspended']);
        }
        $delegationIdentifier = new DelegationIdentifier($delegation->id);
        $policyEvaluator = $this->createMock(PolicyEvaluatorInterface::class);
        if (in_array($case, ['approved', 'denied'], true)) {
            $policyEvaluator->expects(self::once())->method('evaluate')
                ->with(
                    self::callback(static fn (PrincipalEntity $actor): bool => (string) $actor->principalIdentifier() === (string) $principal),
                    Action::DELEGATION_ACCOUNT_SWITCH,
                    Resource::delegationAccount($original, $delegationIdentifier, $target),
                )
                ->willReturn($case === 'approved');
        } else {
            $policyEvaluator->expects(self::never())->method('evaluate');
        }
        app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $service = app(AuthorizationAccountServiceInterface::class);
        $originalAccount = $service->resolveOriginalAccount($identity);
        $this->assertSame($case === 'approved', $service->allowsDelegatedTarget($originalAccount, $target, $delegationIdentifier));
        if ($case === 'approved') {
            $delegation->update(['status' => 'rejected']);
            $this->assertFalse($service->allowsDelegatedTarget($originalAccount, $target, $delegationIdentifier));
        }
    }

    public function testResolvesOwnedOriginalAndRejectsSuspendedMembershipOnNextRead(): void
    {
        $identity = new IdentityIdentifier((string) Str::uuid7());
        CreateIdentity::create($identity);
        $account = (string) Str::uuid7();
        CreateAccount::create($account);
        $principal = Principal::query()->create(['id' => (string) Str::uuid7(), 'identity_id' => (string) $identity, 'account_id' => $account, 'status' => 'active']);
        $service = app(AuthorizationAccountServiceInterface::class);
        $originalAccount = $service->resolveOriginalAccount($identity);
        $this->assertSame($account, (string) $originalAccount->accountIdentifier);
        $this->assertSame($principal->id, (string) $originalAccount->principalIdentifier);
        $principal->update(['status' => 'suspended']);
        $this->expectException(AuthorizationAccountUnavailableException::class);
        $service->resolveOriginalAccount($identity);
    }
}
