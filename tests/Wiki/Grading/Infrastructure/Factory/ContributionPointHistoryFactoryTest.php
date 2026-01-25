<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Grading\Domain\Facotory\ContributionPointHistoryFactoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Grading\Infrastructure\Factory\ContributionPointHistoryFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContributionPointHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(ContributionPointHistoryFactoryInterface::class);
        $this->assertInstanceOf(ContributionPointHistoryFactory::class, $factory);
    }

    /**
     * 正常系: 新規作成のContributionPointHistoryが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateForNewCreation(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable());
        $points = new Point(Point::NEW_EDITOR);
        $resourceType = ResourceType::AGENCY;
        $resourceId = StrTestHelper::generateUuid();
        $roleType = ContributorType::EDITOR;
        $isNewCreation = true;
        $createdAt = new DateTimeImmutable();

        $factory = $this->app->make(ContributionPointHistoryFactoryInterface::class);
        $history = $factory->create(
            $principalIdentifier,
            $yearMonth,
            $points,
            $resourceType,
            $resourceId,
            $roleType,
            $isNewCreation,
            $createdAt,
        );

        $this->assertTrue(UuidValidator::isValid((string) $history->id()));
        $this->assertSame($principalIdentifier, $history->principalIdentifier());
        $this->assertSame($yearMonth, $history->yearMonth());
        $this->assertEquals($points, $history->points());
        $this->assertSame($resourceType, $history->resourceType());
        $this->assertSame($resourceId, (string) $history->resourceIdentifier());
        $this->assertSame($roleType, $history->contributorType());
        $this->assertTrue($history->isNewCreation());
        $this->assertSame($createdAt, $history->createdAt());
    }

    /**
     * 正常系: 更新のContributionPointHistoryが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateForUpdate(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable());
        $points = new Point(Point::UPDATE_EDITOR);
        $resourceType = ResourceType::TALENT;
        $resourceId = StrTestHelper::generateUuid();
        $roleType = ContributorType::APPROVER;
        $isNewCreation = false;
        $createdAt = new DateTimeImmutable();

        $factory = $this->app->make(ContributionPointHistoryFactoryInterface::class);
        $history = $factory->create(
            $principalIdentifier,
            $yearMonth,
            $points,
            $resourceType,
            $resourceId,
            $roleType,
            $isNewCreation,
            $createdAt,
        );

        $this->assertTrue(UuidValidator::isValid((string) $history->id()));
        $this->assertSame($principalIdentifier, $history->principalIdentifier());
        $this->assertSame($yearMonth, $history->yearMonth());
        $this->assertEquals($points, $history->points());
        $this->assertSame($resourceType, $history->resourceType());
        $this->assertSame($resourceId, (string) $history->resourceIdentifier());
        $this->assertSame($roleType, $history->contributorType());
        $this->assertFalse($history->isNewCreation());
        $this->assertSame($createdAt, $history->createdAt());
    }
}
