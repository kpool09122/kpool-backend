<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ListContactsInput implements ListContactsInputPort
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 100;

    private int $perPage;

    public function __construct(
        private IdentityIdentifier $requesterIdentityIdentifier,
        private ?IdentityIdentifier $targetIdentityIdentifier,
        private ?bool $hasReply,
        ?int $perPage = null,
        private int $page = 1,
    ) {
        if ($perPage !== null && ($perPage < 1 || $perPage > self::MAX_PER_PAGE)) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        if ($this->page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }

        $this->perPage = $perPage ?? self::DEFAULT_PER_PAGE;
    }

    public function requesterIdentityIdentifier(): IdentityIdentifier
    {
        return $this->requesterIdentityIdentifier;
    }

    public function targetIdentityIdentifier(): ?IdentityIdentifier
    {
        return $this->targetIdentityIdentifier;
    }

    public function hasReply(): ?bool
    {
        return $this->hasReply;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function page(): int
    {
        return $this->page;
    }
}
