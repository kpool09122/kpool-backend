<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestVerification;

use Source\Account\Account\Application\Exception\DocumentStorageFailedException;

interface RequestVerificationInterface
{
    /**
     * @param RequestVerificationInputPort $input
     * @param RequestVerificationOutputPort $output
     * @return void
     * @throws DocumentStorageFailedException
     */
    public function process(RequestVerificationInputPort $input, RequestVerificationOutputPort $output): void;
}
