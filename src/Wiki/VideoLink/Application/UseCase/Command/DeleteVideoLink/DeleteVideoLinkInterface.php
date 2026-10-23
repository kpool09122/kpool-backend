<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;

interface DeleteVideoLinkInterface
{
    /**
     * @param DeleteVideoLinkInputPort $input
     * @return void
     * @throws DisallowedException
     * @throws VideoLinkNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(DeleteVideoLinkInputPort $input): void;
}
