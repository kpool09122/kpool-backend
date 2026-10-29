<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis;

use InvalidArgumentException;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class ListSitemapWikisInput implements ListSitemapWikisInputPort
{
    /** @throws InvalidArgumentException */
    public function __construct(
        private int $perPage = 500,
        private ?WikiIdentifier $after = null,
    ) {
        if ($perPage < 1 || $perPage > 1000) {
            throw new InvalidArgumentException('perPage must be between 1 and 1000.');
        }
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function after(): ?WikiIdentifier
    {
        return $this->after;
    }
}
