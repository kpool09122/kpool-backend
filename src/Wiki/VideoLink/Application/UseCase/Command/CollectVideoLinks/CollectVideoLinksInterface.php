<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks;

use Source\Wiki\VideoLink\Domain\Exception\VideoLinkCollectionFailedException;
use Source\Wiki\VideoLink\Domain\Exception\VideoSearchFailedException;

interface CollectVideoLinksInterface
{
    /**
     * @throws VideoSearchFailedException
     * @throws VideoLinkCollectionFailedException
     */
    public function process(CollectVideoLinksOutputPort $output): void;
}
