<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;

interface ApproveVerificationInterface
{
    /**
     * @param ApproveVerificationInputPort $input
     * @return AccountVerification
     * @throws AccountVerificationNotFoundException
     */
    public function process(ApproveVerificationInputPort $input): AccountVerification;
}
