<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Repository;

use Source\Auth\Domain\ValueObject\OAuthState;

interface OAuthStateRepositoryInterface
{
    public function store(OAuthState $state): void;

    public function consume(OAuthState $state): void;
}
