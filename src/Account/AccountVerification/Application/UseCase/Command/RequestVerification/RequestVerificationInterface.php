<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use Source\Account\AccountVerification\Application\Exception\DocumentStorageFailedException;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;

interface RequestVerificationInterface
{
    /**
     * @param RequestVerificationInputPort $input
     * @return AccountVerification
     * @throws DocumentStorageFailedException
     */
    public function process(RequestVerificationInputPort $input): AccountVerification;
}
