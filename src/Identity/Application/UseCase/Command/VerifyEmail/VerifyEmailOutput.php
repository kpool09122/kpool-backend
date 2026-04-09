<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use DateTimeInterface;
use Source\Identity\Domain\Entity\AuthCodeSession;

class VerifyEmailOutput implements VerifyEmailOutputPort
{
    private ?AuthCodeSession $session = null;

    public function setSession(AuthCodeSession $session): void
    {
        $this->session = $session;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->session === null) {
            return [];
        }

        $session = $this->session;

        return [
            'email' => (string) $session->email(),
            'verifiedAt' => $session->verifiedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
