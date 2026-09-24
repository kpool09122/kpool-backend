<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterWithPasskey;

use Source\Identity\Domain\Entity\Identity;

class RegisterWithPasskeyOutput implements RegisterWithPasskeyOutputPort
{
    private ?Identity $identity = null;

    private ?string $returnTo = null;

    public function setIdentity(Identity $identity, ?string $returnTo): void
    {
        $this->identity = $identity;
        $this->returnTo = $returnTo;
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
            'profileImage' => $this->identity->profileImage() !== null
                ? (string) $this->identity->profileImage()
                : null,
            'returnTo' => $this->returnTo,
        ];
    }
}
