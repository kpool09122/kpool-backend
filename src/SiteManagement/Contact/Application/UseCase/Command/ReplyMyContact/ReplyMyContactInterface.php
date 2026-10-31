<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact;

use InvalidArgumentException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface ReplyMyContactInterface
{
    /**
     * @throws ContactNotFoundException
     * @throws FailedToSendEmailException
     * @throws UnauthorizedException
     * @throws InvalidArgumentException
     */
    public function process(ReplyMyContactInputPort $input): void;
}
