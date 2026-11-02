<?php

declare(strict_types=1);

namespace Application\Providers\Analyze;

use Illuminate\Support\ServiceProvider;
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

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
