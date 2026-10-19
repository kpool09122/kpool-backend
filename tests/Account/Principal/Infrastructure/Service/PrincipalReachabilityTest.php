<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Service;

use Database\Seeders\AccountAuthorizationSeeder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Principal\Infrastructure\Service\PrincipalReachability;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\SiteManagementPrincipalGroupsFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalReachabilityTest extends TestCase
{
    public function testDeletingAllOriginalGroupsCannotReusePersistedDelegationPermission(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $this->app()->make(AccountAuthorizationSeeder::class)->run();
        $repository = $this->app()->make(PrincipalRepositoryInterface::class);
        $original = $repository->findByIdentityIdentifierAndAccountIdentifier($f->administrator->identityIdentifier(), $f->account);
        self::assertNotNull($original);
        $target = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $original->identityIdentifier(), $f->otherAccount);
        $repository->save($target);
        $owner = $this->app()->make(RoleRepositoryInterface::class)->findSystemByName('Owner');
        self::assertNotNull($owner);
        $policy = new Policy(new PolicyIdentifier(StrTestHelper::generateUuid()), 'Switch', [new Statement(Effect::ALLOW, [Action::DELEGATION_ACCOUNT_SWITCH], [ResourceType::ACCOUNT])], null, new DateTimeImmutable());
        $this->app()->make(PolicyRepositoryInterface::class)->save($policy);
        $owner->addPolicy($policy);
        $this->app()->make(RoleRepositoryInterface::class)->save($owner);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $f->account, 'Original owner', false, new DateTimeImmutable(), [$owner->roleIdentifier()]);
        $group->replaceMembers([$original->principalIdentifier()]);
        $this->app()->make(PrincipalGroupRepositoryInterface::class)->save($group);
        $delegation = new Delegation(new DelegationIdentifier(StrTestHelper::generateUuid()), new AffiliationIdentifier(StrTestHelper::generateUuid()), $f->account, $f->otherAccount, $f->account, DelegationStatus::APPROVED, DelegationDirection::FROM_AGENCY, new DateTimeImmutable(), new DateTimeImmutable(), null);
        $delegationRepository = $this->createStub(DelegationRepositoryInterface::class);
        $delegationRepository->method('findApprovedBetweenAccountIds')->willReturn([$delegation]);
        $this->app()->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $service = $this->app()->make(PrincipalReachability::class);
        self::assertTrue($service->canAccess($original->identityIdentifier(), $f->otherAccount));
        self::assertFalse($service->canAccess($original->identityIdentifier(), $f->otherAccount, [(string) $original->principalIdentifier() => $original], [], $f->account));
        $foreign = $repository->findByIdentityIdentifierAndAccountIdentifier($f->foreign->identityIdentifier(), $f->otherAccount);
        self::assertNotNull($foreign);
        $targets = [$target, $foreign];
        $reachable = $service->filterReachable($targets);
        self::assertArrayHasKey((string) $target->principalIdentifier(), $reachable);
        // A delegation whose source identity is absent must not hide this identity's original account.
        self::assertArrayHasKey((string) $foreign->principalIdentifier(), $reachable);
        $reachable = $service->filterReachable($targets, [], [], $f->account);
        self::assertArrayNotHasKey((string) $target->principalIdentifier(), $reachable);
        self::assertArrayHasKey((string) $foreign->principalIdentifier(), $reachable);
        DB::enableQueryLog();

        try {
            DB::flushQueryLog();
            $service->filterReachable($targets);
            $before = count(DB::getQueryLog());
            for ($i = 0; $i < 10; $i++) {
                $additional = $f->principal($f->account, 'Additional delegated member ' . $i);
                $additionalOriginal = $repository->findByIdentityIdentifierAndAccountIdentifier($additional->identityIdentifier(), $f->account);
                self::assertNotNull($additionalOriginal);
                $group->addMember($additionalOriginal->principalIdentifier());
                $additionalTarget = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $additional->identityIdentifier(), $f->otherAccount);
                $repository->save($additionalTarget);
                $targets[] = $additionalTarget;
            }
            $this->app()->make(PrincipalGroupRepositoryInterface::class)->save($group);
            DB::flushQueryLog();
            $reachable = $service->filterReachable($targets);
            self::assertCount(count($targets), $reachable);
            self::assertSame($before, count(DB::getQueryLog()));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $original->changeStatus(PrincipalStatus::SUSPENDED);
        $reachable = $service->filterReachable($targets, [(string) $original->principalIdentifier() => $original]);
        self::assertArrayNotHasKey((string) $target->principalIdentifier(), $reachable);
        self::assertArrayHasKey((string) $foreign->principalIdentifier(), $reachable);

    }

    public function testOnlyActivePrincipalInActiveUnambiguousAccountIsReachable(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $service = $this->app()->make(PrincipalReachability::class);
        self::assertTrue($service->canAccess($f->administrator->identityIdentifier(), $f->account));
        foreach (['suspended', 'forced_stop'] as $status) {
            DB::table('account_principals')->where('identity_id', (string) $f->administrator->identityIdentifier())->update(['status' => $status]);
            self::assertFalse($service->canAccess($f->administrator->identityIdentifier(), $f->account));
        }
        DB::table('account_principals')->where('identity_id', (string) $f->administrator->identityIdentifier())->update(['status' => 'active']);
        DB::table('accounts')->where('id', (string) $f->account)->update(['status' => 'suspended']);
        self::assertFalse($service->canAccess($f->administrator->identityIdentifier(), $f->account));
    }

    public function testUnrelatedSecondAccountIsNotAnOriginalPrincipalBypass(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $f->administrator->identityIdentifier(), $f->otherAccount);
        $this->app()->make(PrincipalRepositoryInterface::class)->save($principal);
        self::assertFalse($this->app()->make(PrincipalReachability::class)->canAccess($principal->identityIdentifier(), $f->account));
    }

    public function testUnsavedFinalStatusOverridesPersistedActiveStatus(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $principal = $this->app()->make(PrincipalRepositoryInterface::class)->findByIdentityIdentifierAndAccountIdentifier($f->administrator->identityIdentifier(), $f->account);
        self::assertNotNull($principal);
        $principal->changeStatus(PrincipalStatus::SUSPENDED);
        self::assertFalse($this->app()->make(PrincipalReachability::class)->canAccess($principal->identityIdentifier(), $f->account, [(string) $principal->principalIdentifier() => $principal]));
    }
}
