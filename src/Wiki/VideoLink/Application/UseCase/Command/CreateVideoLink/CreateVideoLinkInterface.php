<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkAlreadyExistsException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface CreateVideoLinkInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws WikiNotFoundException
     * @throws VideoMetadataUnavailableException
     * @throws InvalidArgumentException
     * @throws VideoLinkAlreadyExistsException
     */
    public function process(CreateVideoLinkInputPort $input, CreateVideoLinkOutputPort $output): void;
}
