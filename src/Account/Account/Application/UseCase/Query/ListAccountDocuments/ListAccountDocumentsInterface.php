<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountDocuments;

use Source\Account\Account\Application\Exception\AccountDocumentListForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;

interface ListAccountDocumentsInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws AccountDocumentListForbiddenException
     */
    public function process(ListAccountDocumentsInputPort $input, ListAccountDocumentsOutputPort $output): void;
}
