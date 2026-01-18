<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\OAuthState;

interface OAuthStateRepositoryInterface
{
    /**
     * @throws InvalidOAuthStateException
     */
    public function store(OAuthState $state): void;

    /**
     * @throws InvalidOAuthStateException
     */
    public function consume(OAuthState $state): void;
}
