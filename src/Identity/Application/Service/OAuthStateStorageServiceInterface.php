<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\OAuthState;

interface OAuthStateStorageServiceInterface
{
    /**
     * Store state bound to the current browser session.
     *
     * @throws InvalidOAuthStateException
     */
    public function store(OAuthStateSession $session): void;

    /**
     * Consume state only from the browser session that stored it.
     *
     * @throws InvalidOAuthStateException
     */
    public function consume(OAuthState $state): OAuthStateSession;
}
