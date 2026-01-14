<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RequestVerificationInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function verificationType(): VerificationType;

    public function applicantInfo(): ApplicantInfo;

    /**
     * @return DocumentData[]
     */
    public function documents(): array;
}
