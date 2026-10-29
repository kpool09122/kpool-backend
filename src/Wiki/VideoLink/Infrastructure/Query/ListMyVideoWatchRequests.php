<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\Principal as PrincipalModel;
use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use Application\Models\Wiki\VideoLinkWatch as VideoLinkWatchModel;
use Application\Models\Wiki\VideoWatchRequest as VideoWatchRequestModel;
use Application\Models\Wiki\Wiki as WikiModel;
use Carbon\CarbonImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoWatchRequestStatusReadModel;
use Source\Wiki\VideoLink\Domain\Service\VideoWatchRequestPolicy;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

final readonly class ListMyVideoWatchRequests implements ListMyVideoWatchRequestsInterface
{
    public function __construct(
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @return list<VideoWatchRequestStatusReadModel>
     *
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws VideoLinkNotFoundException
     */
    public function process(ListMyVideoWatchRequestsInputPort $input): array
    {
        $model = PrincipalModel::query()->find((string) $input->principalIdentifier());
        if ($model === null) {
            throw new PrincipalNotFoundException();
        }
        $ids = array_values(array_unique(array_map(static fn (VideoLinkIdentifier $id): string => (string) $id, $input->videoLinkIdentifiers())));
        $videos = VideoLinkModel::query()->with('wiki')->whereIn('id', $ids)->orderBy('id')->get();
        if ($videos->count() !== count($ids)) {
            throw new VideoLinkNotFoundException();
        }
        $principal = new Principal($input->principalIdentifier(), new IdentityIdentifier($model->identity_id), new AccountIdentifier($model->account_id), $model->delegation_identifier === null ? null : new DelegationIdentifier($model->delegation_identifier), PrincipalStatus::from($model->status));
        foreach ($videos->pluck('resource_type')->unique() as $resourceType) {
            assert(is_string($resourceType));
            $type = ResourceType::from($resourceType);
            if (! $this->policyEvaluator->evaluate($principal, Action::LIST_MY_VIDEO_WATCH_REQUESTS, new Resource(type: $type))) {
                throw new DisallowedException();
            }
        }
        $now = CarbonImmutable::now()->toDateTimeImmutable();
        $likedIds = VideoLinkLikeModel::query()->whereIn('video_link_id', $ids)->where('principal_id', $model->id)->pluck('video_link_id')->all();
        $lastSends = VideoWatchRequestModel::query()->whereIn('video_link_id', $ids)->where('principal_id', $model->id)
            ->selectRaw('video_link_id, MAX(sent_at) as last_sent_at')->groupBy('video_link_id')->pluck('last_sent_at', 'video_link_id');
        $latestSends = VideoWatchRequestModel::query()->whereIn('video_link_id', $ids)
            ->selectRaw('video_link_id, MAX(sent_at) as last_sent_at')->groupBy('video_link_id')->pluck('last_sent_at', 'video_link_id');
        $translationSetIdentifiers = $videos->pluck('wiki.translation_set_identifier')->filter()->unique()->all();
        $recentByTranslationSet = VideoWatchRequestModel::query()
            ->with('wiki:id,translation_set_identifier')
            ->whereIn('wiki_id', WikiModel::query()->select('id')->whereIn('translation_set_identifier', $translationSetIdentifiers))
            ->where('principal_id', $model->id)->where('sent_at', '>', VideoWatchRequestPolicy::cutoff($now))
            ->orderBy('sent_at')->orderBy('id')->get()
            ->groupBy(static fn (VideoWatchRequestModel $request): string => $request->wiki->translation_set_identifier ?? '');
        $watchedIds = VideoLinkWatchModel::query()->from('wiki_video_link_watches as watches')
            ->join('wiki_video_links as videos', 'videos.id', '=', 'watches.video_link_id')
            ->join('wikis', 'wikis.id', '=', 'videos.wiki_id')
            ->whereIn('videos.id', $ids)->whereColumn('watches.owner_account_id', 'wikis.owner_account_id')
            ->pluck('videos.id')->all();
        $states = [];
        $targetPermissions = [];
        foreach ($videos as $video) {
            $type = ResourceType::from($video->resource_type);
            $wiki = $video->wiki;
            $resource = new Resource(
                type: $type,
                isOfficial: $wiki?->owner_account_id !== null,
                wikiResourceType: $wiki === null ? null : ResourceType::from($wiki->resource_type),
                isManualVideo: VideoUsage::from($video->video_usage)->isManual(),
            );
            $resourceKey = implode(':', [$type->value, $wiki->resource_type ?? '', (int) $resource->isOfficial(), (int) $resource->isManualVideo()]);
            $target = $targetPermissions[$resourceKey] ??= $this->policyEvaluator->evaluate($principal, Action::SEND_VIDEO_WATCH_REQUEST, $resource);
            $liked = in_array($video->id, $likedIds, true);
            $duration = $video->duration_seconds;
            $lastSentAt = $lastSends->get($video->id);
            $latestSentAt = $latestSends->get($video->id);
            assert($lastSentAt === null || is_string($lastSentAt));
            assert($latestSentAt === null || is_string($latestSentAt));
            $last = $lastSentAt === null ? null : CarbonImmutable::parse($lastSentAt);
            $latest = $latestSentAt === null ? null : CarbonImmutable::parse($latestSentAt);
            $recent = $recentByTranslationSet->get($wiki->translation_set_identifier ?? '');
            $recentCount = $recent?->count() ?? 0;
            $ownUntil = $last === null ? null : VideoWatchRequestPolicy::expiresAt($last->toDateTimeImmutable());
            $deleteUntil = $latest === null ? null : VideoWatchRequestPolicy::expiresAt($latest->toDateTimeImmutable());
            $next = $ownUntil !== null && $ownUntil > $now ? $ownUntil : null;
            if ($recent !== null && $recentCount >= VideoWatchRequestPolicy::WEEKLY_LIMIT) {
                $quotaRequest = $recent->get($recentCount - VideoWatchRequestPolicy::WEEKLY_LIMIT);
                assert($quotaRequest !== null);
                $quotaUntil = VideoWatchRequestPolicy::expiresAt($quotaRequest->sent_at->toDateTimeImmutable());
                $next = $next === null || $quotaUntil > $next ? $quotaUntil : $next;
            }
            $reason = ! $target ? 'target_not_eligible' : (in_array($video->id, $watchedIds, true) ? 'already_watched' : (! $liked ? 'like_required' : VideoWatchRequestPolicy::rejectionReason($duration, $liked, $recentCount, $last?->toDateTimeImmutable(), $now)));

            $states[] = new VideoWatchRequestStatusReadModel(
                $video->id,
                $ownUntil !== null && $ownUntil > $now,
                $reason === null,
                $reason,
                $last?->toAtomString(),
                $reason === 'already_watched' ? null : $next?->format(DATE_ATOM),
                max(0, VideoWatchRequestPolicy::WEEKLY_LIMIT - $recentCount),
                $ownUntil !== null && $ownUntil > $now ? $ownUntil->format(DATE_ATOM) : null,
                $deleteUntil !== null && $deleteUntil > $now ? $deleteUntil->format(DATE_ATOM) : null,
            );
        }

        return $states;
    }
}
