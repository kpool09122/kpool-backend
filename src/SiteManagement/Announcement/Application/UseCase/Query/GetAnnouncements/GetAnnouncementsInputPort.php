<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncements;

use Source\Shared\Domain\ValueObject\Translation;

interface GetAnnouncementsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
