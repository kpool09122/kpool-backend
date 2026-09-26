<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PasskeyRecoverySessionStorageServiceInterface
{
    public function issue(IdentityIdentifier $identityIdentifier, string $method): PasskeyRecoveryKey;

    public function requireValid(PasskeyRecoveryKey $key): PasskeyRecoverySession;

    public function consume(PasskeyRecoveryKey $key, IdentityIdentifier $identityIdentifier): void;
}
