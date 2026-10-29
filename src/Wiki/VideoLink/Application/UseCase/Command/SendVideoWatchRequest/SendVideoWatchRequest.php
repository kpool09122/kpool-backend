<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestDurationNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestLikeRequiredException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestResendNotAvailableException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestTargetNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestWeeklyLimitReachedException;
use Source\Wiki\VideoLink\Domain\Factory\VideoWatchRequestFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkLikeRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoWatchRequestRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Service\VideoWatchRequestPolicy;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

final readonly class SendVideoWatchRequest implements SendVideoWatchRequestInterface
{
    public function __construct(
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private WikiRepositoryInterface $wikiRepository,
        private VideoLinkLikeRepositoryInterface $videoLinkLikeRepository,
        private VideoWatchRequestRepositoryInterface $videoWatchRequestRepository,
        private VideoWatchRequestFactoryInterface $videoWatchRequestFactory,
    ) {
    }

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
    public function process(SendVideoWatchRequestInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $video = $this->videoLinkRepository->findById($input->videoLinkIdentifier());
        if ($video === null) {
            throw new VideoLinkNotFoundException();
        }
        $wiki = $this->wikiRepository->findById($video->wikiIdentifier());
        if ($wiki === null) {
            throw new VideoWatchRequestTargetNotEligibleException();
        }
        $resource = new Resource(
            type: $video->resourceType(),
            isOfficial: $wiki->isOfficial(),
            wikiResourceType: $wiki->resourceType(),
            isManualVideo: $video->videoUsage()->isManual(),
        );
        if (! $this->policyEvaluator->evaluate($principal, Action::SEND_VIDEO_WATCH_REQUEST, $resource)) {
            throw new DisallowedException();
        }
        $liked = $this->videoLinkLikeRepository->findByVideoLinkAndPrincipal($video->videoLinkIdentifier(), $principal->principalIdentifier()) !== null;
        if (! $liked) {
            throw new VideoWatchRequestLikeRequiredException();
        }
        $duration = $video->durationSeconds()?->value();
        $now = new DateTimeImmutable();
        $recent = $this->videoWatchRequestRepository->findByTranslationSetAndPrincipal($wiki->translationSetIdentifier(), $principal->principalIdentifier(), VideoWatchRequestPolicy::cutoff($now));
        $last = $this->videoWatchRequestRepository->findLatestByVideoLinkAndPrincipal($video->videoLinkIdentifier(), $principal->principalIdentifier());
        $reason = VideoWatchRequestPolicy::rejectionReason($duration, $liked, count($recent), $last?->sentAt(), $now);
        if ($reason !== null) {
            throw match ($reason) {
                'duration_not_eligible' => new VideoWatchRequestDurationNotEligibleException(),
                'like_required' => new VideoWatchRequestLikeRequiredException(),
                'resend_not_available' => new VideoWatchRequestResendNotAvailableException(),
                'weekly_limit_reached' => new VideoWatchRequestWeeklyLimitReachedException(),
            };
        }
        $this->videoWatchRequestRepository->save($this->videoWatchRequestFactory->create($video->videoLinkIdentifier(), $video->wikiIdentifier(), $principal->principalIdentifier(), $now));
    }
}
