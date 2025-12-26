<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\ValueObject\OAuthState;

interface OAuthStateRepositoryInterface
{
    public function store(OAuthState $state): void;

    public function consume(OAuthState $state): void;
}
