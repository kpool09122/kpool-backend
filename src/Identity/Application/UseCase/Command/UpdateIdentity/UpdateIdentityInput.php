<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateIdentity;

use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

readonly class UpdateIdentityInput implements UpdateIdentityInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private ?DelegationIdentifier $delegationIdentifier,
        private ?IdentityIdentifier $originalIdentityIdentifier,
        private ?IdentityName $identityName,
        private ?Language $language,
        private ?string $base64EncodedImage,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function delegationIdentifier(): ?DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function originalIdentityIdentifier(): ?IdentityIdentifier
    {
        return $this->originalIdentityIdentifier;
    }

    public function identityName(): ?IdentityName
    {
        return $this->identityName;
    }

    public function language(): ?Language
    {
        return $this->language;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }
}
