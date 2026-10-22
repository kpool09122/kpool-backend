<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use Source\Wiki\VideoLinkAutoCollection\Domain\Exception\VideoLinkCollectionFailedException;
use Source\Wiki\VideoLinkAutoCollection\Domain\Exception\VideoSearchFailedException;

interface CollectVideoLinksInterface
{
    /**
     * @throws VideoSearchFailedException
     * @throws VideoLinkCollectionFailedException
     */
    public function process(CollectVideoLinksOutputPort $output): void;
}
