<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Repository;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Monetization\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Monetization\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\Monetization\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Monetization\Principal\Infrastructure\Repository\RoleRepository;
use Source\Monetization\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\MonetizationPrincipalTestData;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationPersistenceTest extends TestCase
{
    public function testActorIdentityAndEffectiveAccountAreBothRequiredForLookup(): void
    {
        $data = MonetizationPrincipalTestData::create();
        CreateAccount::create((string) $data->principal->accountIdentifier());
        CreateIdentity::create($data->principal->identityIdentifier());
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        $repository = new PrincipalRepository();
        $repository->save($data->principal);
        self::assertNull($repository->findByIdentityIdentifierAndAccountIdentifier($data->principal->identityIdentifier(), $otherAccount));
        self::assertNull($repository->findByIdentityIdentifierAndAccountIdentifier(new IdentityIdentifier(StrTestHelper::generateUuid()), $data->principal->accountIdentifier()));
        $other = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $data->principal->identityIdentifier(), $otherAccount, PrincipalStatus::SUSPENDED);
        $repository->save($other);
        self::assertSame((string) $other->principalIdentifier(), (string) $repository->findByIdentityIdentifierAndAccountIdentifier($data->principal->identityIdentifier(), $otherAccount)?->principalIdentifier());
        self::assertSame((string) $data->principal->principalIdentifier(), (string) $repository->findByIdentityIdentifierAndAccountIdentifier($data->principal->identityIdentifier(), $data->principal->accountIdentifier())?->principalIdentifier());
        self::assertCount(2, $repository->findAllByIdentityIdentifier($data->principal->identityIdentifier()));
    }

    public function testIdentityAccountPairIsUnique(): void
    {
        $data = MonetizationPrincipalTestData::create();
        CreateAccount::create((string) $data->principal->accountIdentifier());
        CreateIdentity::create($data->principal->identityIdentifier());
        $repository = new PrincipalRepository();
        $repository->save($data->principal);
        $this->expectException(UniqueConstraintViolationException::class);
        $repository->save(new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $data->principal->identityIdentifier(), $data->principal->accountIdentifier(), PrincipalStatus::ACTIVE));
    }

    public function testStoredPolicyChainRestoresAndImmediatelyReflectsDenyAndMembershipRemoval(): void
    {
        $data = MonetizationPrincipalTestData::create();
        CreateAccount::create((string) $data->principal->accountIdentifier());
        CreateIdentity::create($data->principal->identityIdentifier());
        (new PrincipalRepository())->save($data->principal);
        $policyRepository = new PolicyRepository();
        $roleRepository = new RoleRepository();
        $principalGroupRepository = new PrincipalGroupRepository();
        $id = new PolicyIdentifier(StrTestHelper::generateUuid());
        $allow = new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::PRINCIPAL_GROUP]);
        $policyRepository->save(new Policy($id, 'manage', [$allow], $data->principal->accountIdentifier()));
        $roleRepository->save(new Role($data->role->roleIdentifier(), 'manager', [$id], $data->principal->accountIdentifier()));
        $group = new PrincipalGroup($data->group->principalGroupIdentifier(), 'managers', [$data->role->roleIdentifier()], $data->principal->accountIdentifier(), false, null, [$data->principal->principalIdentifier()]);
        $principalGroupRepository->save($group);
        $principalGroupRepository->save($group);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $restored = $principalGroupRepository->findByPrincipalId($data->principal->principalIdentifier());
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        self::assertCount(3, $queries, 'Membership filtering and eager loading must use a bounded three-query projection.');
        self::assertCount(1, $restored);
        self::assertTrue($restored[0]->hasMember($data->principal->principalIdentifier()));
        self::assertTrue($restored[0]->hasRole($data->role->roleIdentifier()));
        self::assertSame((string) $data->principal->accountIdentifier(), (string) $restored[0]->accountIdentifier());
        self::assertCount(1, $policyRepository->findByIds([$id]));
        $resource = new Resource(ResourceType::PRINCIPAL_GROUP, $data->principal->accountIdentifier());
        $evaluator = new PolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);
        self::assertTrue($evaluator->evaluate($data->principal, Action::PRINCIPAL_GROUP_MANAGE, $resource));
        $deny = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::PRINCIPAL_GROUP]);
        $policyRepository->save(new Policy($id, 'manage', [$allow, $deny], $data->principal->accountIdentifier()));
        self::assertFalse($evaluator->evaluate($data->principal, Action::PRINCIPAL_GROUP_MANAGE, $resource));
        $statements = $policyRepository->findByIds([$id])[(string) $id]->statements();
        self::assertSame(Effect::DENY, $statements[1]->effect());
        self::assertSame([Action::PRINCIPAL_GROUP_MANAGE], $statements[1]->actions());
        self::assertSame([ResourceType::PRINCIPAL_GROUP], $statements[1]->resourceTypes());
        $policyRepository->save(new Policy($id, 'manage', [$allow], $data->principal->accountIdentifier()));
        self::assertTrue($evaluator->evaluate($data->principal, Action::PRINCIPAL_GROUP_MANAGE, $resource));
        $group->replaceMembers([]);
        $principalGroupRepository->save($group);
        self::assertFalse($evaluator->evaluate($data->principal, Action::PRINCIPAL_GROUP_MANAGE, $resource));
        self::assertSame([], $principalGroupRepository->findByPrincipalId($data->principal->principalIdentifier()));
    }
}
