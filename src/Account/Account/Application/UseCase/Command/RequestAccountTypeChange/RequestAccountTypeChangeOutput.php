<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange;

use DateTimeInterface;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;

class RequestAccountTypeChangeOutput implements RequestAccountTypeChangeOutputPort
{
    private ?AccountTypeChangeRequest $request = null;

    public function setRequest(AccountTypeChangeRequest $request): void
    {
        $this->request = $request;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->request === null) {
            return [];
        }
        $request = $this->request;

        return [
            'requestIdentifier' => (string) $request->requestIdentifier(),
            'accountIdentifier' => (string) $request->accountIdentifier(),
            'currentAccountType' => $request->currentAccountType()->value,
            'requestedAccountType' => $request->requestedAccountType()->value,
            'status' => $request->status()->value,
            'requestedAt' => $request->requestedAt()->format(DateTimeInterface::ATOM),
            'reviewedBy' => $request->reviewedBy() !== null ? (string) $request->reviewedBy() : null,
            'reviewedAt' => $request->reviewedAt()?->format(DateTimeInterface::ATOM),
            'rejectionReason' => $request->rejectionReason() !== null ? [
                'code' => $request->rejectionReason()->code()->value,
                'detail' => $request->rejectionReason()->detail(),
            ] : null,
        ];
    }
}
