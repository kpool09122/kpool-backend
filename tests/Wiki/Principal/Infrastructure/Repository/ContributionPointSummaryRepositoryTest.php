<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Principal\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Infrastructure\Repository\ContributionPointSummaryRepository;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateContributionPointSummary;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContributionPointSummaryRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);
        $this->assertInstanceOf(ContributionPointSummaryRepository::class, $repository);
    }

    /**
     * 正常系: 正しくContributionPointSummaryを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $summaryId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $yearMonth = '2024-01';

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier($summaryId),
            new PrincipalIdentifier($principalId),
            new YearMonth($yearMonth),
            new Point(100),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);
        $repository->save($summary);

        $this->assertDatabaseHas('contribution_point_summaries', [
            'id' => $summaryId,
            'principal_id' => $principalId,
            'year_month' => $yearMonth,
            'points' => 100,
        ]);
    }

    /**
     * 正常系: 既存のContributionPointSummaryを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExisting(): void
    {
        $summaryId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $yearMonth = '2024-02';

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );
        CreateContributionPointSummary::create(
            new ContributionPointSummaryIdentifier($summaryId),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => $yearMonth,
                'points' => 50,
            ]
        );

        $this->assertDatabaseHas('contribution_point_summaries', [
            'id' => $summaryId,
            'points' => 50,
        ]);

        // 更新
        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);
        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier($summaryId),
            new PrincipalIdentifier($principalId),
            new YearMonth($yearMonth),
            new Point(150),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );
        $repository->save($summary);

        $this->assertDatabaseHas('contribution_point_summaries', [
            'id' => $summaryId,
            'points' => 150,
        ]);

        // レコードが1件のままであること
        $this->assertDatabaseCount('contribution_point_summaries', 1);
    }

    /**
     * 正常系: PrincipalIdとYearMonthでContributionPointSummaryを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalAndYearMonth(): void
    {
        $summaryId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $yearMonth = '2024-03';

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );
        CreateContributionPointSummary::create(
            new ContributionPointSummaryIdentifier($summaryId),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => $yearMonth,
                'points' => 75,
            ]
        );

        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);
        $result = $repository->findByPrincipalAndYearMonth(
            new PrincipalIdentifier($principalId),
            new YearMonth($yearMonth),
        );

        $this->assertNotNull($result);
        $this->assertSame($summaryId, (string) $result->id());
        $this->assertSame($principalId, (string) $result->principalIdentifier());
        $this->assertSame($yearMonth, (string) $result->yearMonth());
        $this->assertSame(75, $result->points()->value());
    }

    /**
     * 正常系: 該当するContributionPointSummaryが存在しない場合、nullが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalAndYearMonthWhenNotFound(): void
    {
        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);

        $result = $repository->findByPrincipalAndYearMonth(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new YearMonth('2024-01'),
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: YearMonthでContributionPointSummaryを取得できること
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
        CreateContributionPointSummary::create(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId1),
            [
                'year_month' => $yearMonth,
                'points' => 100,
            ]
        );
        CreateContributionPointSummary::create(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId2),
            [
                'year_month' => $yearMonth,
                'points' => 200,
            ]
        );

        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);
        $results = $repository->findByYearMonth(new YearMonth($yearMonth));

        $this->assertCount(2, $results);
        $principalIds = array_map(
            static fn (ContributionPointSummary $s) => (string) $s->principalIdentifier(),
            $results
        );
        $this->assertContains($principalId1, $principalIds);
        $this->assertContains($principalId2, $principalIds);
    }

    /**
     * 正常系: YearMonthで該当するContributionPointSummaryが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByYearMonthWhenNotFound(): void
    {
        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);

        $results = $repository->findByYearMonth(new YearMonth('2020-01'));

        $this->assertSame([], $results);
    }

    /**
     * 正常系: 複数のYearMonthでContributionPointSummaryを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByYearMonths(): void
    {
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        // 異なるYearMonthで3件作成
        CreateContributionPointSummary::create(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => '2024-01',
                'points' => 100,
            ]
        );
        CreateContributionPointSummary::create(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => '2024-02',
                'points' => 150,
            ]
        );
        CreateContributionPointSummary::create(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'year_month' => '2024-03',
                'points' => 200,
            ]
        );

        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);

        // 2024-01と2024-03のみ取得
        $results = $repository->findByYearMonths([
            new YearMonth('2024-01'),
            new YearMonth('2024-03'),
        ]);

        $this->assertCount(2, $results);
        $yearMonths = array_map(
            static fn (ContributionPointSummary $s) => (string) $s->yearMonth(),
            $results
        );
        $this->assertContains('2024-01', $yearMonths);
        $this->assertContains('2024-03', $yearMonths);
        $this->assertNotContains('2024-02', $yearMonths);
    }

    /**
     * 正常系: findByYearMonthsで該当するContributionPointSummaryが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByYearMonthsWhenNotFound(): void
    {
        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);

        $results = $repository->findByYearMonths([
            new YearMonth('2020-01'),
            new YearMonth('2020-02'),
        ]);

        $this->assertSame([], $results);
    }

    /**
     * 正常系: findByYearMonthsに空配列を渡した場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByYearMonthsWithEmptyArray(): void
    {
        $repository = $this->app->make(ContributionPointSummaryRepositoryInterface::class);

        $results = $repository->findByYearMonths([]);

        $this->assertSame([], $results);
    }
}
