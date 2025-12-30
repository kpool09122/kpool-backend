<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

readonly class DeleteAnnouncementInput implements DeleteAnnouncementInputPort
{
    public function __construct(
        private UserIdentifier           $userIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
    ) {
    }

    public function userIdentifier(): UserIdentifier
    {
        return $this->userIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }
}
