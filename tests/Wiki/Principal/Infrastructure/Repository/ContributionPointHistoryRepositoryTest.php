<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Principal\Domain\Repository\ContributionPointHistoryRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\ContributorType;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Infrastructure\Repository\ContributionPointHistoryRepository;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateContributionPointHistory;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContributionPointHistoryRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);
        $this->assertInstanceOf(ContributionPointHistoryRepository::class, $repository);
    }

    /**
     * 正常系: 正しくContributionPointHistoryを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();
        $yearMonth = '2024-01';
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        $history = new ContributionPointHistory(
            new ContributionPointHistoryIdentifier($historyId),
            new PrincipalIdentifier($principalId),
            new YearMonth($yearMonth),
            new Point(10),
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
            ContributorType::EDITOR,
            true,
            $createdAt,
        );

        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);
        $repository->save($history);

        $this->assertDatabaseHas('contribution_point_histories', [
            'id' => $historyId,
            'principal_id' => $principalId,
            'year_month' => $yearMonth,
            'points' => 10,
            'resource_type' => 'talent',
            'resource_id' => $resourceId,
            'contributor_type' => 'editor',
            'is_new_creation' => true,
        ]);
    }

    /**
     * 正常系: PrincipalIdとYearMonthでContributionPointHistoryを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalAndYearMonth(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();
        $yearMonth = '2024-02';

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier($historyId),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => $yearMonth,
                'points' => 5,
                'resource_type' => 'song',
                'resource_id' => $resourceId,
                'contributor_type' => 'approver',
                'is_new_creation' => false,
            ]
        );

        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);
        $results = $repository->findByPrincipalAndYearMonth(
            new PrincipalIdentifier($principalId),
            new YearMonth($yearMonth),
        );

        $this->assertCount(1, $results);
        $this->assertSame($historyId, (string) $results[0]->id());
        $this->assertSame($principalId, (string) $results[0]->principalIdentifier());
        $this->assertSame($yearMonth, (string) $results[0]->yearMonth());
        $this->assertSame(5, $results[0]->points()->value());
        $this->assertSame(ResourceType::SONG, $results[0]->resourceType());
        $this->assertSame($resourceId, (string) $results[0]->resourceIdentifier());
        $this->assertSame(ContributorType::APPROVER, $results[0]->contributorType());
        $this->assertFalse($results[0]->isNewCreation());
    }

    /**
     * 正常系: PrincipalIdとYearMonthで複数のContributionPointHistoryを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalAndYearMonthReturnsMultiple(): void
    {
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $yearMonth = '2024-03';

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        // 2件作成
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => $yearMonth,
                'points' => 10,
                'resource_type' => 'talent',
                'contributor_type' => 'editor',
            ]
        );
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => $yearMonth,
                'points' => 5,
                'resource_type' => 'song',
                'contributor_type' => 'merger',
            ]
        );

        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);
        $results = $repository->findByPrincipalAndYearMonth(
            new PrincipalIdentifier($principalId),
            new YearMonth($yearMonth),
        );

        $this->assertCount(2, $results);
    }

    /**
     * 正常系: 該当するContributionPointHistoryが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalAndYearMonthWhenNotFound(): void
    {
        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);

        $results = $repository->findByPrincipalAndYearMonth(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new YearMonth('2024-01'),
        );

        $this->assertSame([], $results);
    }

    /**
     * 正常系: YearMonthでContributionPointHistoryを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByYearMonth(): void
    {
        $principalId1 = StrTestHelper::generateUuid();
        $principalId2 = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();
        $yearMonth = '2024-04';

        CreateIdentity::create(new IdentityIdentifier($identityId1), ['email' => 'test1@example.com']);
        CreateIdentity::create(new IdentityIdentifier($identityId2), ['email' => 'test2@example.com']);
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId1),
            new IdentityIdentifier($identityId1),
        );
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId2),
            new IdentityIdentifier($identityId2),
        );

        // 異なるPrincipalで2件作成
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId1),
            [
                'year_month' => $yearMonth,
                'points' => 10,
                'resource_type' => 'agency',
            ]
        );
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId2),
            [
                'year_month' => $yearMonth,
                'points' => 3,
                'resource_type' => 'group',
            ]
        );

        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);
        $results = $repository->findByYearMonth(new YearMonth($yearMonth));

        $this->assertCount(2, $results);
        $principalIds = array_map(
            static fn (ContributionPointHistory $h) => (string) $h->principalIdentifier(),
            $results
        );
        $this->assertContains($principalId1, $principalIds);
        $this->assertContains($principalId2, $principalIds);
    }

    /**
     * 正常系: YearMonthで該当するContributionPointHistoryが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByYearMonthWhenNotFound(): void
    {
        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);

        $results = $repository->findByYearMonth(new YearMonth('2020-01'));

        $this->assertSame([], $results);
    }

    /**
     * 正常系: 最後の公開日を取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindLastPublishDate(): void
    {
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();
        $createdAt1 = new DateTimeImmutable('2024-01-10 10:00:00');
        $createdAt2 = new DateTimeImmutable('2024-01-20 15:00:00');

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        // 同じリソースに対して2件作成（日付が異なる）
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => '2024-01',
                'points' => 10,
                'resource_type' => 'talent',
                'resource_id' => $resourceId,
                'contributor_type' => 'editor',
                'is_new_creation' => true,
                'created_at' => $createdAt1,
            ]
        );
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => '2024-01',
                'points' => 5,
                'resource_type' => 'talent',
                'resource_id' => $resourceId,
                'contributor_type' => 'editor',
                'is_new_creation' => false,
                'created_at' => $createdAt2,
            ]
        );

        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);
        $result = $repository->findLastPublishDate(
            new PrincipalIdentifier($principalId),
            ResourceType::TALENT,
            $resourceId,
            ContributorType::EDITOR,
        );

        $this->assertNotNull($result);
        $this->assertSame('2024-01-20', $result->format('Y-m-d'));
    }

    /**
     * 正常系: 該当する公開日が存在しない場合、nullが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindLastPublishDateWhenNotFound(): void
    {
        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);

        $result = $repository->findLastPublishDate(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            StrTestHelper::generateUuid(),
            ContributorType::EDITOR,
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: 異なるroleTypeでは別の公開日が返されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindLastPublishDateWithDifferentRoleType(): void
    {
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();
        $editorCreatedAt = new DateTimeImmutable('2024-01-10 10:00:00');
        $approverCreatedAt = new DateTimeImmutable('2024-01-15 12:00:00');

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        // 同じリソースに対してEDITORとAPPROVERで作成
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => '2024-01',
                'points' => 10,
                'resource_type' => 'talent',
                'resource_id' => $resourceId,
                'contributor_type' => 'editor',
                'created_at' => $editorCreatedAt,
            ]
        );
        CreateContributionPointHistory::create(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => '2024-01',
                'points' => 2,
                'resource_type' => 'talent',
                'resource_id' => $resourceId,
                'contributor_type' => 'approver',
                'created_at' => $approverCreatedAt,
            ]
        );

        $repository = $this->app->make(ContributionPointHistoryRepositoryInterface::class);

        // EDITORの最終公開日を取得
        $editorResult = $repository->findLastPublishDate(
            new PrincipalIdentifier($principalId),
            ResourceType::TALENT,
            $resourceId,
            ContributorType::EDITOR,
        );

        // APPROVERの最終公開日を取得
        $approverResult = $repository->findLastPublishDate(
            new PrincipalIdentifier($principalId),
            ResourceType::TALENT,
            $resourceId,
            ContributorType::APPROVER,
        );

        $this->assertNotNull($editorResult);
        $this->assertSame('2024-01-10', $editorResult->format('Y-m-d'));

        $this->assertNotNull($approverResult);
        $this->assertSame('2024-01-15', $approverResult->format('Y-m-d'));
    }
}
