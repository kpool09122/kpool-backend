<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Source\Identity\Application\Service\PendingAuthorization;

class CreateAuthorizationRequestOutput implements CreateAuthorizationRequestOutputPort
{
    private ?PendingAuthorization $pending = null;

    public function setPending(PendingAuthorization $pending): void
    {
        $this->pending = $pending;
    }

    /** @return array{requestIdentifier: string, expiresAt: string}|array{} */
    public function toArray(): array
    {
        if ($this->pending === null) {
            return [];
        }

        return ['requestIdentifier' => $this->pending->identifier, 'expiresAt' => $this->pending->expiresAt->format(DATE_ATOM)];
    }
}
