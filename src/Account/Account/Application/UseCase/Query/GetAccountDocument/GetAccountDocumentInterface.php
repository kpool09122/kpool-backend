<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountDocument;

use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;

interface GetAccountDocumentInterface
{
    public function process(GetAccountDocumentInputPort $input): AccountDocumentReadModel;
}
