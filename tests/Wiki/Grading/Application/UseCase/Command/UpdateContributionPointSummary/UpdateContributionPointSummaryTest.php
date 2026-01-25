<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInput;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInterface;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryOutput;
use Source\Wiki\Grading\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Grading\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Grading\Domain\Repository\ContributionPointHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdateContributionPointSummaryTest extends TestCase
{
    /**
     * 正常系: サマリーが更新されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessUpdatesSummaries(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $principal1 = StrTestHelper::generateUuid();
        $principal2 = StrTestHelper::generateUuid();

        $history1 = new ContributionPointHistory(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principal1),
            $yearMonth,
            new Point(100),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            ContributorType::EDITOR,
            false,
            new DateTimeImmutable(),
        );
        $history2 = new ContributionPointHistory(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principal2),
            $yearMonth,
            new Point(50),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            ContributorType::EDITOR,
            false,
            new DateTimeImmutable(),
        );

        $historyRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('findByYearMonth')
            ->with(Mockery::on(static fn ($ym) => (string) $ym === '2026-01'))
            ->once()
            ->andReturn([$history1, $history2]);

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonth')
            ->andReturn([]);
        $summaryRepository->shouldReceive('save')
            ->times(2);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->andReturn(StrTestHelper::generateUuid());

        $this->app->instance(ContributionPointHistoryRepositoryInterface::class, $historyRepository);
        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);

        $useCase = $this->app->make(UpdateContributionPointSummaryInterface::class);
        $input = new UpdateContributionPointSummaryInput($yearMonth);
        $output = new UpdateContributionPointSummaryOutput();

        $useCase->process($input, $output);

        $this->assertSame(2, $output->updatedCount());
    }

    /**
     * 正常系: 既存のサマリーが更新されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessUpdatesExistingSummaries(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $principalId = StrTestHelper::generateUuid();

        // 既存のサマリー（ポイント50）
        $existingSummary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(50),
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-01'),
        );

        // 新しい履歴（追加ポイント100）
        $history = new ContributionPointHistory(
            new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(100),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            ContributorType::EDITOR,
            false,
            new DateTimeImmutable(),
        );

        $historyRepository = Mockery::mock(ContributionPointHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('findByYearMonth')
            ->with(Mockery::on(static fn ($ym) => (string) $ym === '2026-01'))
            ->once()
            ->andReturn([$history]);

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonth')
            ->with(Mockery::on(static fn ($ym) => (string) $ym === '2026-01'))
            ->once()
            ->andReturn([$existingSummary]);
        $summaryRepository->shouldReceive('save')
            ->with(Mockery::on(static function ($summary) use ($principalId) {
                // 既存サマリーが更新されていることを確認（履歴のポイント100が設定される）
                return (string) $summary->principalIdentifier() === $principalId
                    && $summary->points()->value() === 100;
            }))
            ->once();

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);

        $this->app->instance(ContributionPointHistoryRepositoryInterface::class, $historyRepository);
        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);

        $useCase = $this->app->make(UpdateContributionPointSummaryInterface::class);
        $input = new UpdateContributionPointSummaryInput($yearMonth);
        $output = new UpdateContributionPointSummaryOutput();

        $useCase->process($input, $output);

        $this->assertSame(1, $output->updatedCount());
        // 既存サマリーのポイントが履歴のポイントに更新されていることを確認
        $this->assertSame(100, $existingSummary->points()->value());
    }
}
