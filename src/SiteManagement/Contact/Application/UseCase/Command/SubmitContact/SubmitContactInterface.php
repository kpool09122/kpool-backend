<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;

interface SubmitContactInterface
{
    /**
     * @param SubmitContactInputPort $input
     * @param SubmitContactOutputPort $output
     * @return void
     * @throws FailedToSendEmailException
     */
    public function process(SubmitContactInputPort $input, SubmitContactOutputPort $output): void;
}
