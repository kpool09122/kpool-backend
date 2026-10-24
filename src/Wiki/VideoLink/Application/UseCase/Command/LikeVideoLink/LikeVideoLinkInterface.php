<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkAlreadyLikedException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;

interface LikeVideoLinkInterface
{
    /**
     * @param LikeVideoLinkInputPort $input
     * @return void
     * @throws DisallowedException
     * @throws VideoLinkAlreadyLikedException
     * @throws VideoLinkNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(LikeVideoLinkInputPort $input): void;
}
