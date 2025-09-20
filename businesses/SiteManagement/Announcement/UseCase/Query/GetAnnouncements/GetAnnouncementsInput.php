<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncements;

use Businesses\Shared\ValueObject\Translation;

readonly class GetAnnouncementsInput implements GetAnnouncementsInputPort
{
    public function __construct(
        private int $limit,
        private string $order,
        private string $sort,
        private string $searchWords,
        private Translation $translation,
    ) {
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function order(): string
    {
        return $this->order;
    }

    public function sort(): string
    {
        return $this->sort;
    }

    public function searchWords(): string
    {
        return $this->searchWords;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }
}
