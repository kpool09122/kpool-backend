<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\OAuthState;

interface OAuthStateRepositoryInterface
{
    /**
     * Store state bound to the current browser session.
     *
     * @throws InvalidOAuthStateException
     */
    public function store(OAuthState $state): void;

    /**
     * Consume state only from the browser session that stored it.
     *
     * @throws InvalidOAuthStateException
     */
    public function consume(OAuthState $state): void;
}
