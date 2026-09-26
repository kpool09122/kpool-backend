<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface SecurityEventRecorderInterface
{
    /** @param array<string, scalar|null> $context */
    public function record(string $event, IdentityIdentifier $identityIdentifier, array $context = []): void;
}
