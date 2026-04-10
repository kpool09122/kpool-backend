<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use Source\Account\AccountVerification\Application\Exception\DocumentStorageFailedException;

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
