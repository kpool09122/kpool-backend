<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail;

use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface GetMyContactDetailInterface
{
    /**
     * @throws ContactNotFoundException
     * @throws UnauthorizedException
     */
    public function process(GetMyContactDetailInputPort $input, GetMyContactDetailOutputPort $output): void;
}
