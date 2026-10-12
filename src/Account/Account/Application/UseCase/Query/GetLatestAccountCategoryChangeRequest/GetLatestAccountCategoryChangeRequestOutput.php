<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;

class GetLatestAccountCategoryChangeRequestOutput implements GetLatestAccountCategoryChangeRequestOutputPort
{
    private ?AccountCategoryChangeRequestReadModel $request = null;

    public function output(?AccountCategoryChangeRequestReadModel $request): void
    {
        $this->request = $request;
    }

    /**
     * @return array{request: array{
     *     requestIdentifier: string,
     *     accountIdentifier: string,
     *     currentAccountCategory: string,
     *     requestedAccountCategory: string,
     *     status: string,
     *     requestedAt: string,
     *     reviewedBy: ?string,
     *     reviewedAt: ?string,
     *     rejectionReason: array{code: string, detail: ?string}|null
     * }|null}
     */
    public function toArray(): array
    {
        return ['request' => $this->request?->toArray()];
    }
}
