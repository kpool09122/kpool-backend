<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContactByOperator;

use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface ReplyContactByOperatorInterface
{
    /**
     * @throws ContactNotFoundException
     * @throws FailedToSendEmailException
     * @throws UnauthorizedException
     */
    public function process(ReplyContactByOperatorInputPort $input): void;
}
