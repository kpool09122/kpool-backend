<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateIdentity;

use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

interface UpdateIdentityInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function delegationIdentifier(): ?DelegationIdentifier;

    public function originalIdentityIdentifier(): ?IdentityIdentifier;

    public function identityName(): ?IdentityName;

    public function language(): ?Language;

    public function base64EncodedImage(): ?string;

    public function profileImageProvided(): bool;
}
