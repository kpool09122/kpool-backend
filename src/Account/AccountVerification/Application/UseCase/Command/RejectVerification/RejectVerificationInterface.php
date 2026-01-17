<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;

interface RejectVerificationInterface
{
    /**
     * @param RejectVerificationInputPort $input
     * @return AccountVerification
     * @throws AccountVerificationNotFoundException
     */
    public function process(RejectVerificationInputPort $input): AccountVerification;
}
