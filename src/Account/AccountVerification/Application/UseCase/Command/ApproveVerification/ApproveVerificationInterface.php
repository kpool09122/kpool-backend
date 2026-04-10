<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;

interface ApproveVerificationInterface
{
    /**
     * @param ApproveVerificationInputPort $input
     * @param ApproveVerificationOutputPort $output
     * @return void
     * @throws AccountVerificationNotFoundException
     */
    public function process(ApproveVerificationInputPort $input, ApproveVerificationOutputPort $output): void;
}
