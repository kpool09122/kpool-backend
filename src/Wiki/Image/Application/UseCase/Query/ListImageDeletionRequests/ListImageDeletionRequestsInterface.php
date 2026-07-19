<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ListImageDeletionRequestsInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ListImageDeletionRequestsInputPort $input, ListImageDeletionRequestsOutputPort $output): void;
}
