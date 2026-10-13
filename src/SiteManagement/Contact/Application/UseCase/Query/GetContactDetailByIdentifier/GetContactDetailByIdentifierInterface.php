<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier;

use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface GetContactDetailByIdentifierInterface
{
    /**
     * @throws ContactNotFoundException
     * @throws UnauthorizedException
     */
    public function process(GetContactDetailByIdentifierInputPort $input, GetContactDetailByIdentifierOutputPort $output): void;
}
