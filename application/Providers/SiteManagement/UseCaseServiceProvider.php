<?php

declare(strict_types=1);

namespace Application\Providers\SiteManagement;

use Illuminate\Support\ServiceProvider;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncement;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContact;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContact;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInterface;
use Source\SiteManagement\Contact\Infrastructure\Query\GetContactDetail;
use Source\SiteManagement\Contact\Infrastructure\Query\GetMyContactDetail;
use Source\SiteManagement\Contact\Infrastructure\Query\ListContacts;
use Source\SiteManagement\Contact\Infrastructure\Query\ListContactsByPrincipal;
use Source\SiteManagement\Contact\Infrastructure\Query\ListMyContacts;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator\GrantSiteManagementOperator;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator\GrantSiteManagementOperatorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipal;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperator;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromService;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(GrantSiteManagementOperatorInterface::class, GrantSiteManagementOperator::class);
        $this->app->singleton(RevokeSiteManagementOperatorInterface::class, RevokeSiteManagementOperator::class);

        $this->app->singleton(WithdrawFromServiceInterface::class, WithdrawFromService::class);

        $this->app->singleton(CreateAnnouncementInterface::class, CreateAnnouncement::class);
        $this->app->singleton(UpdateAnnouncementInterface::class, UpdateAnnouncement::class);
        $this->app->singleton(DeleteAnnouncementInterface::class, DeleteAnnouncement::class);
        $this->app->singleton(SubmitContactInterface::class, SubmitContact::class);
        $this->app->singleton(ReplyContactInterface::class, ReplyContact::class);
        $this->app->singleton(ListContactsByPrincipalInterface::class, ListContactsByPrincipal::class);
        $this->app->singleton(ListContactsInterface::class, ListContacts::class);
        $this->app->singleton(ListMyContactsInterface::class, ListMyContacts::class);
        $this->app->singleton(GetMyContactDetailInterface::class, GetMyContactDetail::class);
        $this->app->singleton(GetContactDetailInterface::class, GetContactDetail::class);
        $this->app->singleton(TranslateAnnouncementInterface::class, TranslateAnnouncement::class);
        $this->app->singleton(PublishAnnouncementInterface::class, PublishAnnouncement::class);
        $this->app->singleton(ProvisionPrincipalInterface::class, ProvisionPrincipal::class);
    }
}
