<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Grading\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Grading\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Grading\Domain\Facotory\ContributionPointHistoryFactoryInterface;
use Source\Wiki\Grading\Domain\Facotory\ContributionPointSummaryFactoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Application\Service\ContributionPointServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContributionPointServiceTest extends TestCase
{
    private function createHistory(): ContributionPointHistory
    {
        return new ContributionPointHistory(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            YearMonth::fromDateTime(new DateTimeImmutable()),
            new Point(Point::NEW_EDITOR),
            ResourceType::AGENCY,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            ContributorType::EDITOR,
            true,
            new DateTimeImmutable(),
        );
    }

    private function createSummary(): ContributionPointSummary
    {
        $now = new DateTimeImmutable();

        return new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            YearMonth::fromDateTime($now),
            new Point(0),
            $now,
            $now,
        );
    }

    /**
     * 正常系: 新規記事で編集者・承認者・マージ者にポイントが付与されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGrantPointsForNewCreation(): void
    {
        $editorId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());

        $historyRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('findLastPublishDate')
            ->with($editorId, ResourceType::AGENCY, $wikiId, ContributorType::EDITOR)
            ->andReturn(null);
        $historyRepository->shouldReceive('save')
            ->times(3);

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByPrincipalAndYearMonth')->andReturn(null);
        $summaryRepository->shouldReceive('save')->times(3);

        $historyFactory = Mockery::mock(ContributionPointHistoryFactoryInterface::class);
        $historyFactory->shouldReceive('create')
            ->times(3)
            ->andReturn($this->createHistory());

        $summaryFactory = Mockery::mock(ContributionPointSummaryFactoryInterface::class);
        $summaryFactory->shouldReceive('create')
            ->times(3)
            ->andReturn($this->createSummary());

        $this->app()->instance(ContributionPointHistoryRepositoryInterface::class, $historyRepository);
        $this->app()->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app()->instance(ContributionPointHistoryFactoryInterface::class, $historyFactory);
        $this->app()->instance(ContributionPointSummaryFactoryInterface::class, $summaryFactory);

        $service = $this->app()->make(ContributionPointServiceInterface::class);
        $service->grantPoints(
            $editorId,
            $approverId,
            $mergerId,
            ResourceType::AGENCY,
            $wikiId,
            true,
            false,
        );
    }

    /**
     * 正常系: 更新記事で編集者・承認者・マージ者にポイントが付与されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGrantPointsForUpdate(): void
    {
        $editorId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());

        $historyRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('findLastPublishDate')
            ->with($editorId, ResourceType::AGENCY, $wikiId, ContributorType::EDITOR)
            ->andReturn(null);
        $historyRepository->shouldReceive('save')
            ->times(3);

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByPrincipalAndYearMonth')->andReturn(null);
        $summaryRepository->shouldReceive('save')->times(3);

        $historyFactory = Mockery::mock(ContributionPointHistoryFactoryInterface::class);
        $historyFactory->shouldReceive('create')
            ->times(3)
            ->andReturn($this->createHistory());

        $summaryFactory = Mockery::mock(ContributionPointSummaryFactoryInterface::class);
        $summaryFactory->shouldReceive('create')
            ->times(3)
            ->andReturn($this->createSummary());

        $this->app()->instance(ContributionPointHistoryRepositoryInterface::class, $historyRepository);
        $this->app()->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app()->instance(ContributionPointHistoryFactoryInterface::class, $historyFactory);
        $this->app()->instance(ContributionPointSummaryFactoryInterface::class, $summaryFactory);

        $service = $this->app()->make(ContributionPointServiceInterface::class);
        $service->grantPoints(
            $editorId,
            $approverId,
            $mergerId,
            ResourceType::AGENCY,
            $wikiId,
            false,
            false,
        );
    }

    /** @return array<string, array{bool, bool, bool, bool, bool, array<string, int>}> */
    public static function pointScenarios(): array
    {
        return [
            'original creation' => [true, false, true, true, false, ['editor' => 10, 'approver' => 2, 'merger' => 3]],
            'original update' => [false, false, true, true, false, ['editor' => 5, 'approver' => 1, 'merger' => 2]],
            'translation creation' => [true, true, true, true, false, ['editor' => 1, 'approver' => 1, 'merger' => 1]],
            'translation update' => [false, true, true, true, false, ['editor' => 1, 'approver' => 1, 'merger' => 1]],
            'translation cooldown' => [false, true, true, true, true, ['approver' => 1, 'merger' => 1]],
            'translation without editor' => [true, true, false, true, false, ['approver' => 1, 'merger' => 1]],
            'translation without merger' => [true, true, true, false, false, ['editor' => 1, 'approver' => 1]],
        ];
    }

    /** @param array<string, int> $expectedPoints */
    #[DataProvider('pointScenarios')]
    public function testRecordsExpectedPointsForEachContributor(
        bool $isNewCreation,
        bool $isTranslation,
        bool $hasEditor,
        bool $hasMerger,
        bool $withinCooldown,
        array $expectedPoints,
    ): void {
        $editorIdentifier = $hasEditor ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : null;
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = $hasMerger ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : null;
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $histories = [];
        $summaries = [];

        $contributionPointHistoryRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        if ($hasEditor) {
            $contributionPointHistoryRepository->shouldReceive('findLastPublishDate')->once()
                ->with($editorIdentifier, ResourceType::AGENCY, $wikiIdentifier, ContributorType::EDITOR)
                ->andReturn($withinCooldown ? new DateTimeImmutable('-3 days') : null);
        }
        $contributionPointHistoryRepository->shouldReceive('save')->times(count($expectedPoints))
            ->andReturnUsing(static function (ContributionPointHistory $history) use (&$histories): void {
                $histories[$history->contributorType()->value] = $history;
            });
        $contributionPointSummaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $contributionPointSummaryRepository->shouldReceive('findByPrincipalAndYearMonth')->andReturn(null);
        $contributionPointSummaryRepository->shouldReceive('save')->times(count($expectedPoints))
            ->andReturnUsing(static function (ContributionPointSummary $summary) use (&$summaries): void {
                $summaries[(string) $summary->principalIdentifier()] = $summary->points()->value();
            });
        $this->app()->instance(ContributionPointHistoryRepositoryInterface::class, $contributionPointHistoryRepository);
        $this->app()->instance(ContributionPointSummaryRepositoryInterface::class, $contributionPointSummaryRepository);

        $this->app()->make(ContributionPointServiceInterface::class)->grantPoints(
            $editorIdentifier,
            $approverIdentifier,
            $mergerIdentifier,
            ResourceType::AGENCY,
            $wikiIdentifier,
            $isNewCreation,
            $isTranslation,
        );

        $identifiers = ['editor' => $editorIdentifier, 'approver' => $approverIdentifier, 'merger' => $mergerIdentifier];
        $this->assertSame(array_keys($expectedPoints), array_keys($histories));
        foreach ($expectedPoints as $type => $points) {
            $history = $histories[$type];
            $this->assertSame((string) $identifiers[$type], (string) $history->principalIdentifier());
            $this->assertSame((string) $wikiIdentifier, (string) $history->wikiIdentifier());
            $this->assertSame($isNewCreation, $history->isNewCreation());
            $this->assertSame($points, $history->points()->value());
            $this->assertSame($points, $summaries[(string) $identifiers[$type]]);
        }
    }

    /**
     * 正常系: クールダウン期間内の編集者にはポイントが付与されないこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGrantPointsEditorCooldownApplied(): void
    {
        $editorId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());

        $recentPublishDate = new DateTimeImmutable()->modify('-3 days');

        $historyRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('findLastPublishDate')
            ->with($editorId, ResourceType::AGENCY, $wikiId, ContributorType::EDITOR)
            ->andReturn($recentPublishDate);
        $historyRepository->shouldReceive('save')
            ->times(2);

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByPrincipalAndYearMonth')->andReturn(null);
        $summaryRepository->shouldReceive('save')->times(2);

        $historyFactory = Mockery::mock(ContributionPointHistoryFactoryInterface::class);
        $historyFactory->shouldReceive('create')
            ->times(2)
            ->andReturn($this->createHistory());

        $summaryFactory = Mockery::mock(ContributionPointSummaryFactoryInterface::class);
        $summaryFactory->shouldReceive('create')
            ->times(2)
            ->andReturn($this->createSummary());

        $this->app()->instance(ContributionPointHistoryRepositoryInterface::class, $historyRepository);
        $this->app()->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app()->instance(ContributionPointHistoryFactoryInterface::class, $historyFactory);
        $this->app()->instance(ContributionPointSummaryFactoryInterface::class, $summaryFactory);

        $service = $this->app()->make(ContributionPointServiceInterface::class);
        $service->grantPoints(
            $editorId,
            $approverId,
            $mergerId,
            ResourceType::AGENCY,
            $wikiId,
            true,
            false,
        );
    }

    /**
     * 正常系: クールダウン期間を超えた編集者にはポイントが付与されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGrantPointsEditorCooldownExpired(): void
    {
        $editorId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());

        $oldPublishDate = new DateTimeImmutable()->modify('-10 days');

        $historyRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('findLastPublishDate')
            ->with($editorId, ResourceType::AGENCY, $wikiId, ContributorType::EDITOR)
            ->andReturn($oldPublishDate);
        $historyRepository->shouldReceive('save')
            ->times(3);

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByPrincipalAndYearMonth')->andReturn(null);
        $summaryRepository->shouldReceive('save')->times(3);

        $historyFactory = Mockery::mock(ContributionPointHistoryFactoryInterface::class);
        $historyFactory->shouldReceive('create')
            ->times(3)
            ->andReturn($this->createHistory());

        $summaryFactory = Mockery::mock(ContributionPointSummaryFactoryInterface::class);
        $summaryFactory->shouldReceive('create')
            ->times(3)
            ->andReturn($this->createSummary());

        $this->app()->instance(ContributionPointHistoryRepositoryInterface::class, $historyRepository);
        $this->app()->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app()->instance(ContributionPointHistoryFactoryInterface::class, $historyFactory);
        $this->app()->instance(ContributionPointSummaryFactoryInterface::class, $summaryFactory);

        $service = $this->app()->make(ContributionPointServiceInterface::class);
        $service->grantPoints(
            $editorId,
            $approverId,
            $mergerId,
            ResourceType::AGENCY,
            $wikiId,
            false,
            false,
        );
    }

    /**
     * 正常系: 既存のサマリーがある場合にポイントが加算されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testGrantPointsUpdatesExistingSummary(): void
    {
        $editorId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());

        $existingSummary = $this->createSummary();
        $initialPoints = $existingSummary->points()->value();

        $historyRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('findLastPublishDate')
            ->with($editorId, ResourceType::AGENCY, $wikiId, ContributorType::EDITOR)
            ->andReturn(null);
        $historyRepository->shouldReceive('save')
            ->times(3);

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByPrincipalAndYearMonth')
            ->andReturn($existingSummary);
        $summaryRepository->shouldReceive('save')
            ->times(3)
            ->with(Mockery::on(static fn (ContributionPointSummary $summary) => $summary->points()->value() > 0));

        $historyFactory = Mockery::mock(ContributionPointHistoryFactoryInterface::class);
        $historyFactory->shouldReceive('create')
            ->times(3)
            ->andReturn($this->createHistory());

        $summaryFactory = Mockery::mock(ContributionPointSummaryFactoryInterface::class);
        $summaryFactory->shouldNotReceive('create');

        $this->app()->instance(ContributionPointHistoryRepositoryInterface::class, $historyRepository);
        $this->app()->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app()->instance(ContributionPointHistoryFactoryInterface::class, $historyFactory);
        $this->app()->instance(ContributionPointSummaryFactoryInterface::class, $summaryFactory);

        $service = $this->app()->make(ContributionPointServiceInterface::class);
        $service->grantPoints(
            $editorId,
            $approverId,
            $mergerId,
            ResourceType::AGENCY,
            $wikiId,
            true,
            false,
        );

        $expectedPoints = $initialPoints + Point::NEW_EDITOR + Point::NEW_APPROVER + Point::NEW_MERGER;
        $this->assertSame($expectedPoints, $existingSummary->points()->value());
    }
}
