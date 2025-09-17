<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncements;

use Businesses\Shared\ValueObject\Translation;

interface GetAnnouncementsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
