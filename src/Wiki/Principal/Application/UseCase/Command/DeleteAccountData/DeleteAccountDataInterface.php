<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

interface DeleteAccountDataInterface
{
    public function process(DeleteAccountDataInputPort $input, DeleteAccountDataOutputPort $output): void;
}
