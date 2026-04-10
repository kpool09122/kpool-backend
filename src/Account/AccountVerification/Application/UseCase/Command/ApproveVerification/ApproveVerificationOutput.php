<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use DateTimeInterface;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;

class ApproveVerificationOutput implements ApproveVerificationOutputPort
{
    private ?AccountVerification $verification = null;

    public function setVerification(AccountVerification $verification): void
    {
        $this->verification = $verification;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->verification === null) {
            return [];
        }

        $verification = $this->verification;

        return [
            'verificationIdentifier' => (string) $verification->verificationIdentifier(),
            'accountIdentifier' => (string) $verification->accountIdentifier(),
            'verificationType' => $verification->verificationType()->value,
            'status' => $verification->status()->value,
            'applicantName' => $verification->applicantInfo()->fullName(),
            'requestedAt' => $verification->requestedAt()->format(DateTimeInterface::ATOM),
            'reviewedBy' => $verification->reviewedBy() !== null ? (string) $verification->reviewedBy() : null,
            'reviewedAt' => $verification->reviewedAt()?->format(DateTimeInterface::ATOM),
            'rejectionReason' => $verification->rejectionReason() !== null ? [
                'code' => $verification->rejectionReason()->code()->value,
                'detail' => $verification->rejectionReason()->detail(),
            ] : null,
            'documents' => array_map(fn ($doc) => [
                'documentIdentifier' => (string) $doc->documentIdentifier(),
                'documentType' => $doc->documentType()->value,
                'documentPath' => (string) $doc->documentPath(),
                'originalFileName' => $doc->originalFileName(),
                'fileSizeBytes' => $doc->fileSizeBytes(),
                'uploadedAt' => $doc->uploadedAt()->format(DateTimeInterface::ATOM),
            ], $verification->documents()),
        ];
    }
}
