<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendAuthCode;

interface SendAuthCodeInterface
{
    public function process(SendAuthCodeInputPort $input): void;
}
