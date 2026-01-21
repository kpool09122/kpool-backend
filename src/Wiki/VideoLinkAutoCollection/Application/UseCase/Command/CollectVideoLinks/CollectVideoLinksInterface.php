<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

interface CollectVideoLinksInterface
{
    public function process(CollectVideoLinksOutputPort $output): void;
}
