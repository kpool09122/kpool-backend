<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

interface SubmitContactInterface
{
    /**
     * @param SubmitContactInputPort $input
     * @return ContactIdentifier
     * @throws FailedToSendEmailException
     */
    public function process(SubmitContactInputPort $input): ContactIdentifier;
}
