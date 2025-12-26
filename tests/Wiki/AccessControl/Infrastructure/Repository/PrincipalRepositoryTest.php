<?php

declare(strict_types=1);

namespace Tests\Wiki\AccessControl\Infrastructure\Repository;

use Application\Models\Wiki\Principal as PrincipalEloquent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\AccessControl\Infrastructure\Repository\PrincipalRepository;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use CreatePrincipal;

    private PrincipalRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PrincipalRepository();
    }

    private function createIdentity(): IdentityIdentifier
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        CreateIdentity::create($identityIdentifier);

        return $identityIdentifier;
    }

    /**
     * 正常系: 正しくIDに紐づくプリンシパルを取得できること.
     *
     * @return void
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $identityIdentifier = $this->createIdentity();
        $principalId = StrTestHelper::generateUlid();

        PrincipalEloquent::query()->create([
            'id' => $principalId,
            'identity_id' => (string) $identityIdentifier,
            'role' => Role::ADMINISTRATOR->value,
            'agency_id' => null,
            'group_ids' => [],
            'talent_ids' => [],
        ]);

        $result = $this->repository->findById(new PrincipalIdentifier($principalId));

        $this->assertNotNull($result);
        $this->assertSame($principalId, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame(Role::ADMINISTRATOR, $result->role());
    }

    public function testFindById_returnsNullWhenNotFound(): void
    {
        $result = $this->repository->findById(new PrincipalIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($result);
    }

    public function testFindByIdentityIdentifier(): void
    {
        $identityIdentifier = $this->createIdentity();
        $principalId = StrTestHelper::generateUlid();

        PrincipalEloquent::query()->create([
            'id' => $principalId,
            'identity_id' => (string) $identityIdentifier,
            'role' => Role::COLLABORATOR->value,
            'agency_id' => null,
            'group_ids' => [],
            'talent_ids' => [],
        ]);

        $result = $this->repository->findByIdentityIdentifier($identityIdentifier);

        $this->assertNotNull($result);
        $this->assertSame($principalId, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame(Role::COLLABORATOR, $result->role());
    }

    public function testFindByIdentityIdentifier_returnsNullWhenNotFound(): void
    {
        $result = $this->repository->findByIdentityIdentifier(new IdentityIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($result);
    }

    public function testSave_createsNewPrincipal(): void
    {
        $identityIdentifier = $this->createIdentity();
        $principalId = StrTestHelper::generateUlid();
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid()];
        $talentIds = [StrTestHelper::generateUlid()];

        $principal = new Principal(
            new PrincipalIdentifier($principalId),
            $identityIdentifier,
            Role::AGENCY_ACTOR,
            $agencyId,
            $groupIds,
            $talentIds,
        );

        $this->repository->save($principal);

        $this->assertDatabaseHas('wiki_principals', [
            'id' => $principalId,
            'identity_id' => (string) $identityIdentifier,
            'role' => Role::AGENCY_ACTOR->value,
            'agency_id' => $agencyId,
        ]);
    }

    public function testSave_updatesExistingPrincipal(): void
    {
        $identityIdentifier = $this->createIdentity();
        $principalId = StrTestHelper::generateUlid();

        PrincipalEloquent::query()->create([
            'id' => $principalId,
            'identity_id' => (string) $identityIdentifier,
            'role' => Role::NONE->value,
            'agency_id' => null,
            'group_ids' => [],
            'talent_ids' => [],
        ]);

        $principal = new Principal(
            new PrincipalIdentifier($principalId),
            $identityIdentifier,
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $this->repository->save($principal);

        $this->assertDatabaseHas('wiki_principals', [
            'id' => $principalId,
            'role' => Role::ADMINISTRATOR->value,
        ]);
    }
}
