<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestDetailReadModel;

class GetAccountCategoryChangeRequestOutput implements GetAccountCategoryChangeRequestOutputPort
{
    private ?AccountCategoryChangeRequestDetailReadModel $detail = null;

    public function output(AccountCategoryChangeRequestDetailReadModel $detail): void
    {
        $this->detail = $detail;
    }

    /** @return array{request: array<string, mixed>, account: array<string, string>, identities: array<int, array{name: string, email: string}>, documents: array<int, array{documentType: string, documentPath: string, uploadedAt: string}>} */
    public function toArray(): array
    {
        if ($this->detail === null) {
            return [
                'request' => [],
                'account' => [],
                'identities' => [],
                'documents' => [],
            ];
        }

        return $this->detail->toArray();
    }
}
