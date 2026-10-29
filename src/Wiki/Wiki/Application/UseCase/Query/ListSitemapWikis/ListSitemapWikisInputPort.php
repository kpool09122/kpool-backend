<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis;

use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ListSitemapWikisInputPort
{
    public function perPage(): int;

    public function after(): ?WikiIdentifier;
}
