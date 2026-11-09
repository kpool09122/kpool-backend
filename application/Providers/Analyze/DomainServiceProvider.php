<?php

declare(strict_types=1);

namespace Application\Providers\Analyze;

use Illuminate\Support\ServiceProvider;
use Source\Analyze\App\Application\Service\DailyMetricsFetchServiceInterface as AppDailyMetricsFetchServiceInterface;
use Source\Analyze\App\Application\Service\RegisteredIdentityCountQueryServiceInterface;
use Source\Analyze\App\Domain\Factory\AppDailySummaryFactoryInterface;
use Source\Analyze\App\Domain\Repository\AppDailySummaryRepositoryInterface;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\App\Infrastructure\Service\DailyMetricsFetchService as AppDailyMetricsFetchService;
use Source\Analyze\App\Infrastructure\Service\RegisteredIdentityCountQueryService;
use Source\Analyze\Principal\Application\Service\AccountMembershipServiceInterface;
use Source\Analyze\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Analyze\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Analyze\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Analyze\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Analyze\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Analyze\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Analyze\Principal\Infrastructure\Factory\PolicyFactory;
use Source\Analyze\Principal\Infrastructure\Factory\PrincipalFactory;
use Source\Analyze\Principal\Infrastructure\Factory\PrincipalGroupFactory;
use Source\Analyze\Principal\Infrastructure\Factory\RoleFactory;
use Source\Analyze\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Analyze\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\Analyze\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Analyze\Principal\Infrastructure\Repository\RoleRepository;
use Source\Analyze\Principal\Infrastructure\Service\AccountMembershipService;
use Source\Analyze\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Analyze\Shared\Application\Service\RegionNormalizerInterface;
use Source\Analyze\Shared\Infrastructure\Service\RegionNormalizer;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface as WikiDailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationsQueryServiceInterface;
use Source\Analyze\Wiki\Domain\Factory\WikiDailySummaryFactoryInterface;
use Source\Analyze\Wiki\Domain\Repository\WikiDailySummaryRepositoryInterface;
use Source\Analyze\Wiki\Infrastructure\Factory\WikiDailySummaryFactory;
use Source\Analyze\Wiki\Infrastructure\Repository\WikiDailySummaryRepository;
use Source\Analyze\Wiki\Infrastructure\Service\DailyMetricsFetchService as WikiDailyMetricsFetchService;
use Source\Analyze\Wiki\Infrastructure\Service\DailyWikiOperationsQueryService;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RegionNormalizerInterface::class, RegionNormalizer::class);
        $this->app->singleton(AppDailyMetricsFetchServiceInterface::class, AppDailyMetricsFetchService::class);
        $this->app->singleton(WikiDailyMetricsFetchServiceInterface::class, WikiDailyMetricsFetchService::class);
        $this->app->singleton(RegisteredIdentityCountQueryServiceInterface::class, RegisteredIdentityCountQueryService::class);
        $this->app->singleton(DailyWikiOperationsQueryServiceInterface::class, DailyWikiOperationsQueryService::class);
        $this->app->singleton(WikiDailySummaryFactoryInterface::class, WikiDailySummaryFactory::class);
        $this->app->singleton(WikiDailySummaryRepositoryInterface::class, WikiDailySummaryRepository::class);
        $this->app->singleton(AppDailySummaryFactoryInterface::class, AppDailySummaryFactory::class);
        $this->app->singleton(AppDailySummaryRepositoryInterface::class, AppDailySummaryRepository::class);
        $this->app->singleton(PrincipalRepositoryInterface::class, PrincipalRepository::class);
        $this->app->singleton(PrincipalFactoryInterface::class, PrincipalFactory::class);
        $this->app->singleton(PrincipalGroupRepositoryInterface::class, PrincipalGroupRepository::class);
        $this->app->singleton(PrincipalGroupFactoryInterface::class, PrincipalGroupFactory::class);
        $this->app->singleton(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->singleton(RoleFactoryInterface::class, RoleFactory::class);
        $this->app->singleton(PolicyRepositoryInterface::class, PolicyRepository::class);
        $this->app->singleton(PolicyFactoryInterface::class, PolicyFactory::class);
        $this->app->singleton(PolicyEvaluatorInterface::class, PolicyEvaluator::class);
        $this->app->singleton(AccountMembershipServiceInterface::class, AccountMembershipService::class);
    }
}
