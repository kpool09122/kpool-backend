<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

use Source\Analyze\App\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\App\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\Factory\AppDailySummaryFactoryInterface;
use Source\Analyze\App\Domain\Repository\AppDailySummaryRepositoryInterface;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;

readonly class ImportAppDailySummary implements ImportAppDailySummaryInterface
{
    public function __construct(
        private AppDailySummaryRepositoryInterface $appDailySummaryRepository,
        private AppDailySummaryFactoryInterface $appDailySummaryFactory,
        private DailyMetricsFetchServiceInterface $dailyMetricsFetchService,
    ) {
    }

    /**
     * @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     * @throws InvalidDailySummaryException
     * @throws MetricDefinitionMismatchException
     */
    public function process(ImportAppDailySummaryInputPort $input, ImportAppDailySummaryOutputPort $output): void
    {
        $data = $this->dailyMetricsFetchService->fetchSummary($input->day(), $input->now(), $input->maxPages());
        $summary = $this->appDailySummaryRepository->findByDay($input->day());
        if ($summary === null) {
            $summary = $this->appDailySummaryFactory->create($data->applicationUsers(), $data->registeredUsers());
        } else {
            $summary->replaceAnalysis($data->applicationUsers(), $data->registeredUsers());
        }
        $summary->replaceCountries($data->countries());
        $this->appDailySummaryRepository->save($summary);
        $output->setSummaryCount(1);
    }
}
