<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\Service\AffiliationQueryServiceInterface;
use Source\Wiki\Principal\Infrastructure\Service\AffiliationQueryService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationQueryServiceTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $query = $this->app->make(AffiliationQueryServiceInterface::class);

        $this->assertInstanceOf(AffiliationQueryService::class, $query);
    }

    /**
     * 正常系: Affiliation が存在する場合に AccountIdentifier が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindAccountIdentifiersByAffiliationIdReturnsIdentifiersWhenFound(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $affiliation = new Affiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::ACTIVE,
            null,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository
            ->shouldReceive('findById')
            ->once()
            ->with($affiliationIdentifier)
            ->andReturn($affiliation);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $query = $this->app->make(AffiliationQueryServiceInterface::class);

        $result = $query->findAccountIdentifiersByAffiliationId($affiliationIdentifier);

        $this->assertNotNull($result);
        $this->assertSame($agencyAccountIdentifier, $result['agencyAccountIdentifier']);
        $this->assertSame($talentAccountIdentifier, $result['talentAccountIdentifier']);
    }

    /**
     * 正常系: Affiliation が存在しない場合に null が返されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFindAccountIdentifiersByAffiliationIdReturnsNullWhenNotFound(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository
            ->shouldReceive('findById')
            ->once()
            ->with($affiliationIdentifier)
            ->andReturnNull();

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $query = $this->app->make(AffiliationQueryServiceInterface::class);

        $result = $query->findAccountIdentifiersByAffiliationId($affiliationIdentifier);

        $this->assertNull($result);
    }
}
