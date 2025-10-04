<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;

readonly class DeleteAnnouncementInput implements DeleteAnnouncementInputPort
{
    public function __construct(
        private TranslationSetIdentifier $translationSetIdentifier,
    ) {
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }
}
