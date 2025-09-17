<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\UseCase\Command\SubmitContact;

use Businesses\SiteManagement\Contact\UseCase\Exception\FailedToSendEmailException;

interface SubmitContactInterface
{
    /**
     * @param SubmitContactInputPort $input
     * @return void
     * @throws FailedToSendEmailException
     */
    public function process(SubmitContactInputPort $input): void;
}
