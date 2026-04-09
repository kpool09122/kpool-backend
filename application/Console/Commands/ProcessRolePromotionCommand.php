<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Application\Jobs\Wiki\ProcessRolePromotionJob;
use Illuminate\Console\Command;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInput;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInterface;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionOutput;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInput;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryInterface;
use Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary\UpdateContributionPointSummaryOutput;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;

class ProcessRolePromotionCommand extends Command
{
    #[\Override]
    protected $signature = 'wiki:process-role-promotion
                            {--month= : 処理対象月（YYYY-MM形式、指定がなければ今月）}
                            {--sync : ジョブをキューに入れず同期実行}';

    #[\Override]
    protected $description = 'Wiki Collaboratorの昇格・降格処理を実行する';

    public function handle(
        UpdateContributionPointSummaryInterface $updateSummaryUseCase,
        ProcessRolePromotionInterface $rolePromotionUseCase,
    ): int {
        $monthString = $this->option('month');
        $isSync = (bool) $this->option('sync');

        if ($monthString !== null) {
            if (! preg_match('/^\d{4}-\d{2}$/', $monthString)) {
                $this->error('Invalid month format. Please use YYYY-MM format.');

                return self::FAILURE;
            }
            $yearMonth = YearMonth::fromString($monthString);
        } else {
            $yearMonth = YearMonth::current();
        }

        $this->info("Processing role promotion for: {$yearMonth}");

        if ($isSync) {
            return $this->executeSync($updateSummaryUseCase, $rolePromotionUseCase, $yearMonth);
        }

        ProcessRolePromotionJob::dispatch($yearMonth);
        $this->info('ProcessRolePromotionJob dispatched successfully.');

        return self::SUCCESS;
    }

    private function executeSync(
        UpdateContributionPointSummaryInterface $updateSummaryUseCase,
        ProcessRolePromotionInterface $rolePromotionUseCase,
        YearMonth $yearMonth,
    ): int {
        // Step 1: Update summaries
        $summaryInput = new UpdateContributionPointSummaryInput($yearMonth);
        $summaryOutput = new UpdateContributionPointSummaryOutput();
        $updateSummaryUseCase->process($summaryInput, $summaryOutput);

        // Step 2: Process role promotions/demotions
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $rolePromotionUseCase->process($input, $output);

        $this->info('Processing completed.');
        $this->table(
            ['Type', 'Count'],
            [
                ['Summaries Updated', $summaryOutput->updatedCount()],
                ['Promoted', count($output->promoted())],
                ['Demoted', count($output->demoted())],
                ['Warned', count($output->warned())],
            ]
        );

        if (count($output->promoted()) > 0) {
            $this->info('Promoted principals:');
            foreach ($output->promoted() as $principalId) {
                $this->line("  - {$principalId}");
            }
        }

        if (count($output->demoted()) > 0) {
            $this->info('Demoted principals:');
            foreach ($output->demoted() as $principalId) {
                $this->line("  - {$principalId}");
            }
        }

        if (count($output->warned()) > 0) {
            $this->info('Warned principals:');
            foreach ($output->warned() as $principalId) {
                $this->line("  - {$principalId}");
            }
        }

        return self::SUCCESS;
    }
}
