<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ListImageDeletionRequestsInput implements ListImageDeletionRequestsInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ?int $perPage = null,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }
}
