<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RequestVerificationInput implements RequestVerificationInputPort
{
    /**
     * @param DocumentData[] $documents
     */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private VerificationType $verificationType,
        private ApplicantInfo $applicantInfo,
        private array $documents,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function verificationType(): VerificationType
    {
        return $this->verificationType;
    }

    public function applicantInfo(): ApplicantInfo
    {
        return $this->applicantInfo;
    }

    /**
     * @return DocumentData[]
     */
    public function documents(): array
    {
        return $this->documents;
    }
}
