<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;

interface ApproveAgencyOutputPort
{
    public function setDraftAgency(DraftAgency $draftAgency): void;

    /**
     * @return array{language: ?string, name: ?string, CEO: ?string, foundedIn: ?string, description: ?string, status: ?string}
     */
    public function toArray(): array;
}
