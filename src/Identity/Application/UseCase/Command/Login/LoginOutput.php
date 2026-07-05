<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use Source\Identity\Domain\Entity\Identity;

class LoginOutput implements LoginOutputPort
{
    private ?Identity $identity = null;

    private ?string $returnTo = null;

    public function setIdentity(Identity $identity): void
    {
        $this->identity = $identity;
    }

    public function setReturnTo(?string $returnTo): void
    {
        $this->returnTo = $returnTo;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->identity === null) {
            return [];
        }

        $identity = $this->identity;

        return [
            'identityIdentifier' => (string) $identity->identityIdentifier(),
            'identityName' => (string) $identity->identityName(),
            'email' => (string) $identity->email(),
            'language' => $identity->language()->value,
            'profileImage' => $identity->profileImage() !== null ? (string) $identity->profileImage() : null,
            'returnTo' => $this->returnTo,
        ];
    }
}
