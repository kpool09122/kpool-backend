<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements;

use InvalidArgumentException;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class ListDraftAnnouncementsInput implements ListDraftAnnouncementsInputPort
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 100;

    private int $perPage;

    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        ?int $perPage = null,
        private string $order = 'desc',
        private string $sort = 'published_date',
        private string $searchWords = '',
    ) {
        if ($perPage !== null && ($perPage < 1 || $perPage > self::MAX_PER_PAGE)) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }
        $this->perPage = $perPage ?? self::DEFAULT_PER_PAGE;
    }

    public function perPage(): int
    {
        return $this->perPage;
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

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
