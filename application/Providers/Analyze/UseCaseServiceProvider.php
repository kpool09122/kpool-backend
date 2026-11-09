<?php

declare(strict_types=1);

namespace Application\Providers\Analyze;

use Illuminate\Support\ServiceProvider;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummary;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryInterface;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInterface;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInterface;
use Source\Analyze\App\Infrastructure\Query\GetAppDailySummaryReport;
use Source\Analyze\App\Infrastructure\Query\GetAppRegionalDailySummaryReport;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipal;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromService;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Analyze\Principal\Infrastructure\Query\GetCurrentPrincipal;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummary;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInterface;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInterface;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport\GetWikiRegionalDailySummaryReportInterface;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiDailySummaryReport;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiRegionalDailySummaryReport;

class UseCaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GetWikiRegionalDailySummaryReportInterface::class, GetWikiRegionalDailySummaryReport::class);
        $this->app->singleton(GetAppRegionalDailySummaryReportInterface::class, GetAppRegionalDailySummaryReport::class);
        $this->app->singleton(ImportAppDailySummaryInterface::class, ImportAppDailySummary::class);
        $this->app->singleton(ImportWikiDailySummaryInterface::class, ImportWikiDailySummary::class);
        $this->app->singleton(GetAppDailySummaryReportInterface::class, GetAppDailySummaryReport::class);
        $this->app->singleton(GetWikiDailySummaryReportInterface::class, GetWikiDailySummaryReport::class);
        $this->app->singleton(GetCurrentPrincipalInterface::class, GetCurrentPrincipal::class);
        $this->app->singleton(CreatePrincipalInterface::class, CreatePrincipal::class);
        $this->app->singleton(WithdrawFromServiceInterface::class, WithdrawFromService::class);
    }
}
