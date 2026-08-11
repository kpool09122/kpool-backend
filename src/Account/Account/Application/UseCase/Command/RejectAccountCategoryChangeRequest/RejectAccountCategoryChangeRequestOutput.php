<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

use DateTimeInterface;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;

class RejectAccountCategoryChangeRequestOutput implements RejectAccountCategoryChangeRequestOutputPort
{
    private ?AccountCategoryChangeRequest $request = null;

    public function setRequest(AccountCategoryChangeRequest $request): void
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
            'currentAccountCategory' => $request->currentAccountCategory()->value,
            'requestedAccountCategory' => $request->requestedAccountCategory()->value,
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
