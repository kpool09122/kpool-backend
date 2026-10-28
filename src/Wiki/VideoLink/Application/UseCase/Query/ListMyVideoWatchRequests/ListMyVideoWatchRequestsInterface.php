<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoWatchRequestStatusReadModel;

interface ListMyVideoWatchRequestsInterface
{
    /**
     * @return list<VideoWatchRequestStatusReadModel>
     *
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws VideoLinkNotFoundException
     */
    public function process(ListMyVideoWatchRequestsInputPort $input): array;
}
