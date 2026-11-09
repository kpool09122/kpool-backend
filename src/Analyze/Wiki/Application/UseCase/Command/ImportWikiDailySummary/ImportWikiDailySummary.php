<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Wiki\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\Wiki\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\Factory\WikiDailySummaryFactoryInterface;
use Source\Analyze\Wiki\Domain\Repository\WikiDailySummaryRepositoryInterface;

readonly class ImportWikiDailySummary implements ImportWikiDailySummaryInterface
{
    public function __construct(
        private WikiDailySummaryRepositoryInterface $wikiDailySummaryRepository,
        private WikiDailySummaryFactoryInterface $wikiDailySummaryFactory,
        private DailyMetricsFetchServiceInterface $dailyMetricsFetchService,
    ) {
    }

    /**
     * @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     * @throws InvalidDailySummaryException
     * @throws MetricDefinitionMismatchException
     */
    public function process(ImportWikiDailySummaryInputPort $input, ImportWikiDailySummaryOutputPort $output): void
    {
        $dataset = $this->dailyMetricsFetchService->fetchSummary($input->day(), $input->now(), $input->maxPages());
        $summaryCount = 0;
        foreach ($this->wikiDailySummaryRepository->findAllByDay($input->day()) as $summary) {
            $data = $dataset->removeFor($summary);
            if ($data === null) {
                $data = $dataset->zeroFor($summary);
            }
            $summary->replaceAnalysis($data->views(), $data->viewers(), $data->engagementTime(), $data->submissions(), $data->publications());
            $summary->correctTranslationSet($data->translationSetIdentifier());
            $summary->replaceRegions($dataset->regionsFor($summary));
            $this->wikiDailySummaryRepository->save($summary);
            $summaryCount++;
        }
        foreach ($dataset->remaining() as $data) {
            $summary = $this->wikiDailySummaryFactory->create(
                $data->wikiIdentifier(),
                $data->translationSetIdentifier(),
                $data->language(),
                $data->country(),
                $data->views(),
                $data->viewers(),
                $data->engagementTime(),
                $data->submissions(),
                $data->publications(),
            );
            $summary->replaceRegions($dataset->regionsFor($summary));
            $this->wikiDailySummaryRepository->save($summary);
            $summaryCount++;
        }
        $output->setSummaryCount($summaryCount);
    }
}
