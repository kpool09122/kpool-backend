<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveVerification;

use Source\Account\Account\Application\Exception\AccountVerificationNotFoundException;

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
