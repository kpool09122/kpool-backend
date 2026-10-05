<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface GetContactDetailInterface
{
    /**
     * @throws ContactNotFoundException
     * @throws UnauthorizedException
     */
    public function process(GetContactDetailInputPort $input, GetContactDetailOutputPort $output): void;
}
