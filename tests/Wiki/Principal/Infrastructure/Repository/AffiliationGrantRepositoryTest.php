<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\Repository\AffiliationGrantRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Principal\Infrastructure\Repository\AffiliationGrantRepository;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationGrantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function test__construct(): void
    {
        $repository = $this->app->make(AffiliationGrantRepositoryInterface::class);
        $this->assertInstanceOf(AffiliationGrantRepository::class, $repository);
    }

    /**
     * 正常系: 保存と取得ができること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindById(): void
    {
        $repository = $this->app->make(AffiliationGrantRepositoryInterface::class);

        $affiliationGrant = $this->createAffiliationGrant();

        $repository->save($affiliationGrant);

        $found = $repository->findById($affiliationGrant->affiliationGrantIdentifier());

        $this->assertNotNull($found);
        $this->assertSame((string) $affiliationGrant->affiliationGrantIdentifier(), (string) $found->affiliationGrantIdentifier());
        $this->assertSame((string) $affiliationGrant->affiliationIdentifier(), (string) $found->affiliationIdentifier());
        $this->assertSame((string) $affiliationGrant->policyIdentifier(), (string) $found->policyIdentifier());
        $this->assertSame((string) $affiliationGrant->roleIdentifier(), (string) $found->roleIdentifier());
        $this->assertSame((string) $affiliationGrant->principalGroupIdentifier(), (string) $found->principalGroupIdentifier());
        $this->assertSame($affiliationGrant->type(), $found->type());
    }

    /**
     * 正常系: 存在しないIDで検索した場合nullを返すこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(AffiliationGrantRepositoryInterface::class);

        $found = $repository->findById(new AffiliationGrantIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($found);
    }

    /**
     * 正常系: AffiliationIdで複数取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAffiliationId(): void
    {
        $repository = $this->app->make(AffiliationGrantRepositoryInterface::class);

        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $agencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        $repository->save($talentSideGrant);
        $repository->save($agencySideGrant);

        $found = $repository->findByAffiliationId($affiliationIdentifier);

        $this->assertCount(2, $found);
    }

    /**
     * 正常系: AffiliationIdとTypeで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAffiliationIdAndType(): void
    {
        $repository = $this->app->make(AffiliationGrantRepositoryInterface::class);

        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $talentSideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $agencySideGrant = $this->createAffiliationGrant($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        $repository->save($talentSideGrant);
        $repository->save($agencySideGrant);

        $foundTalent = $repository->findByAffiliationIdAndType($affiliationIdentifier, AffiliationGrantType::TALENT_SIDE);
        $foundAgency = $repository->findByAffiliationIdAndType($affiliationIdentifier, AffiliationGrantType::AGENCY_SIDE);

        $this->assertNotNull($foundTalent);
        $this->assertNotNull($foundAgency);
        $this->assertSame(AffiliationGrantType::TALENT_SIDE, $foundTalent->type());
        $this->assertSame(AffiliationGrantType::AGENCY_SIDE, $foundAgency->type());
    }

    /**
     * 正常系: AffiliationIdとTypeで見つからない場合nullを返すこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAffiliationIdAndTypeReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(AffiliationGrantRepositoryInterface::class);

        $found = $repository->findByAffiliationIdAndType(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            AffiliationGrantType::TALENT_SIDE
        );

        $this->assertNull($found);
    }

    /**
     * 正常系: 削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $repository = $this->app->make(AffiliationGrantRepositoryInterface::class);

        $affiliationGrant = $this->createAffiliationGrant();

        $repository->save($affiliationGrant);

        $found = $repository->findById($affiliationGrant->affiliationGrantIdentifier());
        $this->assertNotNull($found);

        $repository->delete($affiliationGrant);

        $foundAfterDelete = $repository->findById($affiliationGrant->affiliationGrantIdentifier());
        $this->assertNull($foundAfterDelete);
    }

    private function createAffiliationGrant(
        ?AffiliationIdentifier $affiliationIdentifier = null,
        AffiliationGrantType $type = AffiliationGrantType::TALENT_SIDE,
    ): AffiliationGrant {
        return new AffiliationGrant(
            new AffiliationGrantIdentifier(StrTestHelper::generateUuid()),
            $affiliationIdentifier ?? new AffiliationIdentifier(StrTestHelper::generateUuid()),
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            new RoleIdentifier(StrTestHelper::generateUuid()),
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $type,
            new DateTimeImmutable(),
        );
    }
}
