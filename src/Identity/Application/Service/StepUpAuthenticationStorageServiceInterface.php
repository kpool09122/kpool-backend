<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface StepUpAuthenticationStorageServiceInterface
{
    public function store(StepUpAuthentication $authentication): void;

    public function requireValid(
        IdentityIdentifier $expectedIdentityIdentifier,
        StepUpAuthenticationScope $expectedScope,
    ): StepUpAuthentication;
}
