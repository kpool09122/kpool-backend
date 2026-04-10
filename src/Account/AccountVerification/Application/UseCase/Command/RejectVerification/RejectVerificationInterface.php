<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;

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
