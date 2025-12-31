<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateGroup;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalRepositoryTest extends TestCase
{
    /**
     * 正常系: 正しくIDに紐づくプリンシパルを取得できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     * @return void
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findById($principalIdentifier);

        $this->assertNotNull($result);
        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame(Role::ADMINISTRATOR, $result->role());
    }

    /**
     * 正常系: 指定したIDを持つプリンシパルが存在しない場合、NULLが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findById(new PrincipalIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 指定したIdentityIDでプリンシパルが作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdentifier(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, [
            'role' => Role::COLLABORATOR,
        ]);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findByIdentityIdentifier($identityIdentifier);

        $this->assertNotNull($result);
        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame(Role::COLLABORATOR, $result->role());
    }

    /**
     * 正常系: 指定したIdentity IDでプリンシパルが取得できない場合、NULLが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdentifierWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findByIdentityIdentifier(new IdentityIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しく新規のプリンシパルを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewPrincipal(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $principalId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        CreateGroup::create($groupId);
        $groupIds = [$groupId];
        $talentIds = [StrTestHelper::generateUuid()];

        $principal = new Principal(
            new PrincipalIdentifier($principalId),
            $identityIdentifier,
            Role::AGENCY_ACTOR,
            $agencyId,
            $groupIds,
            $talentIds,
        );

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $repository->save($principal);

        $this->assertDatabaseHas('wiki_principals', [
            'id' => $principalId,
            'identity_id' => (string) $identityIdentifier,
            'role' => Role::AGENCY_ACTOR->value,
            'agency_id' => $agencyId,
        ]);

        $this->assertDatabaseHas('wiki_principal_groups', [
            'wiki_principal_id' => $principalId,
            'group_id' => $groupId,
        ]);
    }

    /**
     * 正常系: 正しく既存のプリンシパルを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testSaveWithExistingPrincipal(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);


        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, [
            'role' => Role::NONE,
        ]);

        $principal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $repository->save($principal);

        $this->assertDatabaseHas('wiki_principals', [
            'id' => (string) $principalIdentifier,
            'role' => Role::ADMINISTRATOR->value,
        ]);
    }
}
