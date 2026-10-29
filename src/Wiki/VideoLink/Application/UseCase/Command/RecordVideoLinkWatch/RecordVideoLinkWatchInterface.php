<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\RecordVideoLinkWatch;

interface RecordVideoLinkWatchInterface
{
    public function process(RecordVideoLinkWatchInputPort $input): void;
}
