<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalGroupRepositoryTest extends TestCase
{
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
        $target = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'administrators', [], $account);
        $other = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'administrators', [], $otherAccount);
        $general = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'general', [], $account, true);
        foreach ([$other, $general, $target] as $group) {
            $repository->save($group);
        }

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
        $target = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'administrators', [], $account);
        $general = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'general', [], $account, true);
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
        $repository->save(new PrincipalGroup($id, 'test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009')));
        $entity = $repository->findById($id);
        self::assertNotNull($entity);
        self::assertSame('test', $entity->name());
    }
}
