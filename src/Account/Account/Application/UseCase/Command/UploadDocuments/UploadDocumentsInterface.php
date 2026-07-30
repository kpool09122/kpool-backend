<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UploadDocuments;

interface UploadDocumentsInterface
{
    public function process(UploadDocumentsInputPort $input, UploadDocumentsOutputPort $output): void;
}
