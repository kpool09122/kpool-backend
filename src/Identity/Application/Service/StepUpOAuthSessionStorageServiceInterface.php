<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\StepUpOAuthSession;

interface StepUpOAuthSessionStorageServiceInterface
{
    public function store(OAuthState $state, StepUpOAuthSession $session): void;

    public function consume(OAuthState $state): ?StepUpOAuthSession;
}
