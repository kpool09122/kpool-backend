<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey;

interface CompleteStepUpWithPasskeyInterface
{
    public function process(CompleteStepUpWithPasskeyInputPort $input, CompleteStepUpWithPasskeyOutputPort $output): void;
}
