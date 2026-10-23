<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkAlreadyExistsException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface CreateVideoLinkInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws WikiNotFoundException
     * @throws VideoLinkAlreadyExistsException
     */
    public function process(CreateVideoLinkInputPort $input): void;
}
