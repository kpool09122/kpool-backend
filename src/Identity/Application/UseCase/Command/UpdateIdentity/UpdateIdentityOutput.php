<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateIdentity;

use Source\Identity\Domain\Entity\Identity;

class UpdateIdentityOutput implements UpdateIdentityOutputPort
{
    private ?Identity $identity = null;

    public function setIdentity(Identity $identity): void
    {
        $this->identity = $identity;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->identity === null) {
            return [];
        }

        return [
            'identityIdentifier' => (string) $this->identity->identityIdentifier(),
            'identityName' => (string) $this->identity->identityName(),
            'email' => (string) $this->identity->email(),
            'language' => $this->identity->language()->value,
            'profileImage' => $this->identity->profileImage() !== null ? (string) $this->identity->profileImage() : null,
        ];
    }
}
