<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestDurationNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestLikeRequiredException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestResendNotAvailableException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestTargetNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestWeeklyLimitReachedException;

interface SendVideoWatchRequestInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws VideoLinkNotFoundException
     * @throws VideoWatchRequestTargetNotEligibleException
     * @throws VideoWatchRequestDurationNotEligibleException
     * @throws VideoWatchRequestLikeRequiredException
     * @throws VideoWatchRequestResendNotAvailableException
     * @throws VideoWatchRequestWeeklyLimitReachedException
     */
    public function process(SendVideoWatchRequestInputPort $input): void;
}
