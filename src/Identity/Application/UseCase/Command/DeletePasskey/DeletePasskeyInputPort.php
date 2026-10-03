<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DeletePasskey;

use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface DeletePasskeyInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function passkeyIdentifier(): PasskeyCredentialIdentifier;
}
