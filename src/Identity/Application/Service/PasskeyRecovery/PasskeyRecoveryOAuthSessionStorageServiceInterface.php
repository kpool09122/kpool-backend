<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use Source\Identity\Domain\ValueObject\OAuthState;

interface PasskeyRecoveryOAuthSessionStorageServiceInterface
{
    public function store(OAuthState $state, PasskeyRecoveryOAuthSession $session): void;

    public function consume(OAuthState $state): ?PasskeyRecoveryOAuthSession;
}
