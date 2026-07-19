<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ListImageDeletionRequestsInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function perPage(): int;
}
