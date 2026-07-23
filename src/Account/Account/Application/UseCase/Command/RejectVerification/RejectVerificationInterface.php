<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectVerification;

use Source\Account\Account\Application\Exception\AccountVerificationNotFoundException;

interface RejectVerificationInterface
{
    /**
     * @param RejectVerificationInputPort $input
     * @param RejectVerificationOutputPort $output
     * @return void
     * @throws AccountVerificationNotFoundException
     */
    public function process(RejectVerificationInputPort $input, RejectVerificationOutputPort $output): void;
}
