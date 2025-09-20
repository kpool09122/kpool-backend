<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;

interface SubmitContactInterface
{
    /**
     * @param SubmitContactInputPort $input
     * @return void
     * @throws FailedToSendEmailException
     */
    public function process(SubmitContactInputPort $input): void;
}
