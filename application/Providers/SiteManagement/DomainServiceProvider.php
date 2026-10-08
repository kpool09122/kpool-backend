<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Source\Account\Principal\Domain\Event\PrincipalCreated;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Infrastructure\Service\Encryption\EncryptionService;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Infrastructure\Adapters\Repository\AnnouncementRepository;
use Source\SiteManagement\Announcement\Infrastructure\Factory\AnnouncementFactory;
use Source\SiteManagement\Announcement\Infrastructure\Factory\DraftAnnouncementFactory;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Source\SiteManagement\Contact\Infrastructure\Adapters\Repository\ContactRepository;
use Source\SiteManagement\Contact\Infrastructure\Adapters\Repository\ReplyContactRepository;
use Source\SiteManagement\Contact\Infrastructure\Factory\ContactFactory;
use Source\SiteManagement\Contact\Infrastructure\Factory\ReplyContactFactory;
use Source\SiteManagement\Contact\Infrastructure\Service\ContactEmailService;
use Source\SiteManagement\Principal\Application\EventHandler\IdentityWithdrawingHandler;
use Source\SiteManagement\Principal\Application\EventHandler\PrincipalCreatedHandler;
use Source\SiteManagement\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Factory\RoleFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Infrastructure\Factory\PolicyFactory;
use Source\SiteManagement\Principal\Infrastructure\Factory\PrincipalFactory;
use Source\SiteManagement\Principal\Infrastructure\Factory\PrincipalGroupFactory;
use Source\SiteManagement\Principal\Infrastructure\Factory\RoleFactory;
use Source\SiteManagement\Principal\Infrastructure\Repository\PolicyRepository;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\SiteManagement\Principal\Infrastructure\Repository\RoleRepository;
use Source\SiteManagement\Principal\Infrastructure\Service\PolicyEvaluator;

class DomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(PrincipalFactoryInterface::class, PrincipalFactory::class);
        $this->app->singleton(PrincipalGroupFactoryInterface::class, PrincipalGroupFactory::class);
        $this->app->singleton(RoleFactoryInterface::class, RoleFactory::class);
        $this->app->singleton(PolicyFactoryInterface::class, PolicyFactory::class);
        $this->app->singleton(PrincipalRepositoryInterface::class, PrincipalRepository::class);
        $this->app->singleton(PrincipalGroupRepositoryInterface::class, PrincipalGroupRepository::class);
        $this->app->singleton(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->singleton(PolicyRepositoryInterface::class, PolicyRepository::class);
        $this->app->singleton(PolicyEvaluatorInterface::class, PolicyEvaluator::class);

        $this->app->make(Dispatcher::class)->listen(PrincipalCreated::class, [PrincipalCreatedHandler::class, 'handle']);
        $this->app->make(Dispatcher::class)->listen(IdentityWithdrawing::class, [IdentityWithdrawingHandler::class, 'handle']);
        $this->app->singleton(AnnouncementFactoryInterface::class, AnnouncementFactory::class);
        $this->app->singleton(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $this->app->singleton(ContactFactoryInterface::class, ContactFactory::class);
        $this->app->singleton(ReplyContactFactoryInterface::class, ReplyContactFactory::class);
        $this->app->singleton(DraftAnnouncementFactoryInterface::class, DraftAnnouncementFactory::class);
        $this->app->singleton(ContactRepositoryInterface::class, ContactRepository::class);
        $this->app->singleton(ReplyContactRepositoryInterface::class, ReplyContactRepository::class);
        $this->app->singleton(EncryptionServiceInterface::class, EncryptionService::class);
        $this->app->singleton(
            ContactEmailServiceInterface::class,
            fn (): ContactEmailService => new ContactEmailService(
                new Email((string) config('mail.from.address'))
            )
        );
    }
}
