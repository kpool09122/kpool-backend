<?php

declare(strict_types=1);

namespace Application\Providers\Wiki;

use Illuminate\Support\ServiceProvider;
use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControl;
use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControlInterface;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgency;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\CreateAgency\CreateAgency;
use Source\Wiki\Agency\Application\UseCase\Command\CreateAgency\CreateAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\EditAgency\EditAgency;
use Source\Wiki\Agency\Application\UseCase\Command\EditAgency\EditAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgency;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\RejectAgency\RejectAgency;
use Source\Wiki\Agency\Application\UseCase\Command\RejectAgency\RejectAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgency;
use Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency\SubmitAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgency;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesInterface;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgency\GetAgencyInterface;
use Source\Wiki\Agency\Infrastracture\Adapters\Query\GetAgencies;
use Source\Wiki\Agency\Infrastracture\Adapters\Query\GetAgency;
use Source\Wiki\Group\Application\UseCase\Command\ApproveGroup\ApproveGroup;
use Source\Wiki\Group\Application\UseCase\Command\ApproveGroup\ApproveGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\CreateGroup\CreateGroup;
use Source\Wiki\Group\Application\UseCase\Command\CreateGroup\CreateGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\EditGroup\EditGroup;
use Source\Wiki\Group\Application\UseCase\Command\EditGroup\EditGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\PublishGroup\PublishGroup;
use Source\Wiki\Group\Application\UseCase\Command\PublishGroup\PublishGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroup;
use Source\Wiki\Group\Application\UseCase\Command\RejectGroup\RejectGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\SubmitGroup\SubmitGroup;
use Source\Wiki\Group\Application\UseCase\Command\SubmitGroup\SubmitGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroup;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInterface;
use Source\Wiki\Song\Application\UseCase\Command\ApproveSong\ApproveSong;
use Source\Wiki\Song\Application\UseCase\Command\ApproveSong\ApproveSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSong;
use Source\Wiki\Song\Application\UseCase\Command\CreateSong\CreateSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSong;
use Source\Wiki\Song\Application\UseCase\Command\EditSong\EditSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSong;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\RejectSong\RejectSong;
use Source\Wiki\Song\Application\UseCase\Command\RejectSong\RejectSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\SubmitSong\SubmitSong;
use Source\Wiki\Song\Application\UseCase\Command\SubmitSong\SubmitSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSong;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSongInterface;
use Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent\ApproveTalent;
use Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent\ApproveTalentInterface;
use Source\Wiki\Talent\Application\UseCase\Command\CreateTalent\CreateTalent;
use Source\Wiki\Talent\Application\UseCase\Command\CreateTalent\CreateTalentInterface;
use Source\Wiki\Talent\Application\UseCase\Command\EditTalent\EditTalent;
use Source\Wiki\Talent\Application\UseCase\Command\EditTalent\EditTalentInterface;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalent;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalentInterface;
use Source\Wiki\Talent\Application\UseCase\Command\RejectTalent\RejectTalent;
use Source\Wiki\Talent\Application\UseCase\Command\RejectTalent\RejectTalentInterface;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalent;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalentInterface;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalent;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalentInterface;

class UseCaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CreateTalentInterface::class, CreateTalent::class);
        $this->app->singleton(EditTalentInterface::class, EditTalent::class);
        $this->app->singleton(CreateGroupInterface::class, CreateGroup::class);
        $this->app->singleton(EditGroupInterface::class, EditGroup::class);
        $this->app->singleton(CreateSongInterface::class, CreateSong::class);
        $this->app->singleton(EditSongInterface::class, EditSong::class);
        $this->app->singleton(CreateAgencyInterface::class, CreateAgency::class);
        $this->app->singleton(EditAgencyInterface::class, EditAgency::class);
        $this->app->singleton(GetAgencyInterface::class, GetAgency::class);
        $this->app->singleton(GetAgenciesInterface::class, GetAgencies::class);
        $this->app->singleton(ChangeAccessControlInterface::class, ChangeAccessControl::class);
        $this->app->singleton(SubmitAgencyInterface::class, SubmitAgency::class);
        $this->app->singleton(ApproveAgencyInterface::class, ApproveAgency::class);
        $this->app->singleton(RejectAgencyInterface::class, RejectAgency::class);
        $this->app->singleton(PublishAgencyInterface::class, PublishAgency::class);
        $this->app->singleton(TranslateAgencyInterface::class, TranslateAgency::class);
        $this->app->singleton(SubmitGroupInterface::class, SubmitGroup::class);
        $this->app->singleton(ApproveGroupInterface::class, ApproveGroup::class);
        $this->app->singleton(RejectGroupInterface::class, RejectGroup::class);
        $this->app->singleton(PublishGroupInterface::class, PublishGroup::class);
        $this->app->singleton(TranslateGroupInterface::class, TranslateGroup::class);
        $this->app->singleton(SubmitTalentInterface::class, SubmitTalent::class);
        $this->app->singleton(ApproveTalentInterface::class, ApproveTalent::class);
        $this->app->singleton(RejectTalentInterface::class, RejectTalent::class);
        $this->app->singleton(PublishTalentInterface::class, PublishTalent::class);
        $this->app->singleton(TranslateTalentInterface::class, TranslateTalent::class);
        $this->app->singleton(SubmitSongInterface::class, SubmitSong::class);
        $this->app->singleton(ApproveSongInterface::class, ApproveSong::class);
        $this->app->singleton(RejectSongInterface::class, RejectSong::class);
        $this->app->singleton(PublishSongInterface::class, PublishSong::class);
        $this->app->singleton(TranslateSongInterface::class, TranslateSong::class);
    }
}
