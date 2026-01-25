<?php

declare(strict_types=1);

namespace Application\Jobs\Wiki;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInput;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInterface;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionOutput;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInput;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInterface;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryOutput;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Throwable;

class ProcessRolePromotionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly YearMonth $yearMonth,
    ) {
    }

    public function handle(
        UpdateContributionPointSummaryInterface $updateSummaryUseCase,
        ProcessRolePromotionInterface $rolePromotionUseCase,
    ): void {
        Log::info('ProcessRolePromotionJob started', [
            'year_month' => (string) $this->yearMonth,
        ]);

        // Step 1: Update summaries
        $summaryInput = new UpdateContributionPointSummaryInput($this->yearMonth);
        $summaryOutput = new UpdateContributionPointSummaryOutput();
        $updateSummaryUseCase->process($summaryInput, $summaryOutput);

        // Step 2: Process role promotions/demotions
        $input = new ProcessRolePromotionInput($this->yearMonth);
        $output = new ProcessRolePromotionOutput();

        $rolePromotionUseCase->process($input, $output);

        Log::info('ProcessRolePromotionJob completed', [
            'year_month' => (string) $this->yearMonth,
            'summaries_updated' => $summaryOutput->updatedCount(),
            'promoted_count' => count($output->promoted()),
            'demoted_count' => count($output->demoted()),
            'warned_count' => count($output->warned()),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessRolePromotionJob failed', [
            'year_month' => (string) $this->yearMonth,
            'message' => $exception->getMessage(),
        ]);
    }

    public function uniqueId(): string
    {
        return (string) $this->yearMonth;
    }
}
