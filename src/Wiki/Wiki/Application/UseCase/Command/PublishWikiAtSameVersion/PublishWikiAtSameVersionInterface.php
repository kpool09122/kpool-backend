<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\PublishedWikiRequiredException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface PublishWikiAtSameVersionInterface
{
    /**
     * @param PublishWikiAtSameVersionInputPort $input
     * @param PublishWikiAtSameVersionOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws PublishedWikiRequiredException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishWikiAtSameVersionInputPort $input, PublishWikiAtSameVersionOutputPort $output): void;
}
