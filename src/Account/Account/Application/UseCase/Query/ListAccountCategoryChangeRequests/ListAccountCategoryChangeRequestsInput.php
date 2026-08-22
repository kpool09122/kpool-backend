<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests;

use InvalidArgumentException;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountCategory;

readonly class ListAccountCategoryChangeRequestsInput implements ListAccountCategoryChangeRequestsInputPort
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 100;

    public function __construct(
        private Principal $principal,
        private ?string $status = null,
        private ?string $requestedAccountCategory = null,
        ?int $perPage = null,
        private int $page = 1,
    ) {
        if ($this->status !== null && AccountCategoryChangeRequestStatus::tryFrom($this->status) === null) {
            throw new InvalidArgumentException('Invalid account category change request status.');
        }

        if ($this->requestedAccountCategory !== null && AccountCategory::tryFrom($this->requestedAccountCategory) === null) {
            throw new InvalidArgumentException('Invalid requested account category.');
        }

        if ($perPage !== null && ($perPage < 1 || $perPage > self::MAX_PER_PAGE)) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        if ($this->page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }

        $this->perPage = $perPage ?? self::DEFAULT_PER_PAGE;
    }

    private int $perPage;

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function requestedAccountCategory(): ?string
    {
        return $this->requestedAccountCategory;
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
