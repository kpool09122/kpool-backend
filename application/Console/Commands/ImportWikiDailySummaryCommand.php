<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInput;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInterface;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryOutput;
use Throwable;

class ImportWikiDailySummaryCommand extends Command
{
    protected $signature = 'analyze:import-wiki-daily-summary {--from= : Inclusive UTC date} {--to= : Inclusive UTC date}';

    protected $description = 'Import and atomically publish completed daily Wiki summaries';

    public function handle(ImportWikiDailySummaryInterface $importWikiDailySummary): int
    {
        try {
            $now = Carbon::now('UTC')->toDateTimeImmutable();
            $from = $this->option('from');
            $to = $this->option('to');
            $days = config('analyze.reimport_days');
            $maxPages = config('analyze.max_pages_per_day');
            if (! is_int($days) || $days < 1 || $days > 366 || ! is_int($maxPages) || $maxPages < 1 || $maxPages > 100000) {
                throw new InvalidArgumentException('Invalid Analyze reimport/page configuration.');
            }
            if (($from === null) !== ($to === null) || ($from !== null && ! is_string($from)) || ($to !== null && ! is_string($to))) {
                throw new InvalidArgumentException('Specify both --from and --to.');
            }
            $end = new UtcDate($to ?? $now->modify('-3 days')->format('Y-m-d'));
            $start = new UtcDate($from ?? $end->instant()->modify('-' . ($days - 1) . ' days')->format('Y-m-d'));
            $period = new ReportingPeriod($start, $end, $now);
            $failed = false;
            foreach ($period->days() as $day) {
                try {
                    $output = new ImportWikiDailySummaryOutput();
                    DB::transaction(fn () => $importWikiDailySummary->process(new ImportWikiDailySummaryInput($day, Carbon::now('UTC')->toDateTimeImmutable(), $maxPages), $output));
                    $this->line($day . ' complete summaries=' . $output->summaryCount());
                } catch (Throwable) {
                    $this->error($day . ' summary import failed; published values retained.');
                    $failed = true;
                }
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        } catch (Throwable) {
            $this->error('Analyze Wiki daily summary import failed. Check date range, API and history configuration.');

            return self::FAILURE;
        }
    }
}
