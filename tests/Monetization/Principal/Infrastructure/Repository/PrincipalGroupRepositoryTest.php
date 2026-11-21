<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\Monetization\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Monetization\Principal\Infrastructure\Repository\RoleRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\MonetizationPrincipalTestData;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalGroupRepositoryTest extends TestCase
{
    public function testDescriptionSurvivesSaveAndReloadAndCanBeCleared(): void
    {
        $accountIdentifier = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $principalGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'Description Test', [], $accountIdentifier, false, null, []);
        $principalGroupRepository = new PrincipalGroupRepository();
        $principalGroup->setDescription(new PrincipalGroupDescription('Manages group memberships.'));
        $principalGroupRepository->save($principalGroup);
        $restored = $principalGroupRepository->findById($principalGroup->principalGroupIdentifier());
        self::assertNotNull($restored);
        self::assertSame('Manages group memberships.', (string) $restored->description());
        self::assertSame('Manages group memberships.', (string) $principalGroupRepository->findByAccountId($accountIdentifier)[0]->description());
        $principalGroupRepository->save($restored);
        self::assertSame('Manages group memberships.', (string) $principalGroupRepository->findById($principalGroup->principalGroupIdentifier())?->description());
        $restored->setDescription(null);
        $principalGroupRepository->save($restored);
        self::assertNull($principalGroupRepository->findById($principalGroup->principalGroupIdentifier())?->description());
    }

    protected function setUp(): void
    {
        parent::setUp();
        CreateAccount::create('00000000-0000-7000-8000-000000000009');
    }

    public function testFindByAccountIdAndNameScopesToBothAccountAndName(): void
    {
        $repository = new PrincipalGroupRepository();
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        $target = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'administrators', [], $account, false, null, []);
        $other = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'administrators', [], $otherAccount, false, null, []);
        $general = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'General', [], $account, true, null, []);
        foreach ([$other, $general, $target] as $group) {
            $repository->save($group);
        }

        self::assertCount(2, $repository->findByAccountId($account));
        self::assertCount(1, $repository->findByAccountId($otherAccount));
        self::assertSame([], $repository->findByAccountId(new AccountIdentifier(StrTestHelper::generateUuid())));

        $loaded = $repository->findByAccountIdAndName($account, 'administrators');
        self::assertNotNull($loaded);
        self::assertSame((string) $target->principalGroupIdentifier(), (string) $loaded->principalGroupIdentifier());
        self::assertFalse($loaded->isDefault());
        self::assertNull($repository->findByAccountIdAndName($account, 'missing'));
        self::assertNull($repository->findByAccountIdAndName(new AccountIdentifier(StrTestHelper::generateUuid()), 'administrators'));
    }

    public function testDeleteOnlyRemovesTheSpecifiedGroup(): void
    {
        $repository = new PrincipalGroupRepository();
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $target = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'administrators', [], $account, false, null, []);
        $general = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'General', [], $account, true, null, []);
        $repository->save($target);
        $repository->save($general);
        $repository->delete($target);

        self::assertNull($repository->findById($target->principalGroupIdentifier()));
        self::assertNotNull($repository->findById($general->principalGroupIdentifier()));
    }

    public function testSaveAndLoadRoundTrip(): void
    {
        $id = new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000099');
        $repository = new PrincipalGroupRepository();
        $repository->save(new PrincipalGroup($id, 'test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009'), false, null, []));
        $entity = $repository->findById($id);
        self::assertNotNull($entity);
        self::assertSame('test', $entity->name());
    }

    public function testSaveReplacesRolesAndMembersAndCanClearThem(): void
    {
        $first = MonetizationPrincipalTestData::create();
        $second = MonetizationPrincipalTestData::create();
        $principalRepository = new PrincipalRepository();
        foreach ([$first, $second] as $data) {
            CreateIdentity::create($data->principal->identityIdentifier(), ['email' => (string) $data->principal->identityIdentifier() . '@example.com']);
            $principalRepository->save($data->principal);
        }
        $roleRepository = new RoleRepository();
        $roleRepository->save($first->role);
        $roleRepository->save(new Role($second->role->roleIdentifier(), 'Manager', [], null));
        $roleIds = [$first->role->roleIdentifier(), $second->role->roleIdentifier()];
        $memberIds = [$first->principal->principalIdentifier(), $second->principal->principalIdentifier()];
        $group = new PrincipalGroup($first->group->principalGroupIdentifier(), 'Managers', $roleIds, $first->principal->accountIdentifier(), false, null, $memberIds);
        $otherGroup = new PrincipalGroup($second->group->principalGroupIdentifier(), 'Other', $roleIds, $first->principal->accountIdentifier(), false, null, $memberIds);
        $repository = new PrincipalGroupRepository();
        $repository->save($group);
        $repository->save($otherGroup);
        $repository->save($group);

        $restored = $repository->findById($group->principalGroupIdentifier());
        self::assertNotNull($restored);
        self::assertEqualsCanonicalizing($roleIds, $restored->roles());
        self::assertEqualsCanonicalizing($memberIds, array_values($restored->members()));

        $group->removeRole($roleIds[0]);
        $group->removeMember($memberIds[0]);
        $repository->save($group);
        $restored = $repository->findById($group->principalGroupIdentifier());
        self::assertNotNull($restored);
        self::assertEquals([$roleIds[1]], $restored->roles());
        self::assertEquals([$memberIds[1]], array_values($restored->members()));

        $group->removeRole($roleIds[1]);
        $group->removeMember($memberIds[1]);
        $repository->save($group);
        $restored = $repository->findById($group->principalGroupIdentifier());
        self::assertNotNull($restored);
        self::assertSame([], $restored->roles());
        self::assertSame([], $restored->members());
        $otherRestored = $repository->findById($otherGroup->principalGroupIdentifier());
        self::assertNotNull($otherRestored);
        self::assertEqualsCanonicalizing($roleIds, $otherRestored->roles());
        self::assertEqualsCanonicalizing($memberIds, array_values($otherRestored->members()));
    }
}
