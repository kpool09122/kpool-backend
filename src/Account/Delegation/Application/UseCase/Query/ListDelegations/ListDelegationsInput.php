<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query\ListDelegations;

use InvalidArgumentException;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class ListDelegationsInput implements ListDelegationsInputPort
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 100;
    public const VIEWER_ROLE_REQUESTER = 'requester';
    public const VIEWER_ROLE_APPROVER = 'approver';

    private int $perPage;

    public function __construct(
        private Principal $principal,
        private ?string $status = null,
        private ?string $viewerRole = null,
        ?int $perPage = null,
        private int $page = 1,
    ) {
        if ($this->status !== null && DelegationStatus::tryFrom($this->status) === null) {
            throw new InvalidArgumentException('Invalid delegation status.');
        }
        if ($this->viewerRole !== null && ! in_array($this->viewerRole, [self::VIEWER_ROLE_REQUESTER, self::VIEWER_ROLE_APPROVER], true)) {
            throw new InvalidArgumentException('Invalid delegation viewer role.');
        }
        if ($perPage !== null && ($perPage < 1 || $perPage > self::MAX_PER_PAGE)) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }
        if ($this->page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }
        $this->perPage = $perPage ?? self::DEFAULT_PER_PAGE;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function viewerRole(): ?string
    {
        return $this->viewerRole;
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
