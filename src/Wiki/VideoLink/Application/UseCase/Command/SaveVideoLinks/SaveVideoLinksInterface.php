<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

interface SaveVideoLinksInterface
{
    public function process(SaveVideoLinksInputPort $input): void;
}
