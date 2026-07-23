<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestVerification;

use Source\Account\Account\Domain\ValueObject\ApplicantInfo;
use Source\Account\Account\Domain\ValueObject\VerificationType;
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
