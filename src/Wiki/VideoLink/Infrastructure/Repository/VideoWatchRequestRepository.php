<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Repository;

use Application\Models\Wiki\VideoWatchRequest as VideoWatchRequestModel;
use Application\Models\Wiki\Wiki as WikiModel;
use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoWatchRequest;
use Source\Wiki\VideoLink\Domain\Repository\VideoWatchRequestRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoWatchRequestIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class VideoWatchRequestRepository implements VideoWatchRequestRepositoryInterface
{
    public function save(VideoWatchRequest $videoWatchRequest): void
    {
        VideoWatchRequestModel::query()->create([
            'id' => (string) $videoWatchRequest->videoWatchRequestIdentifier(),
            'video_link_id' => (string) $videoWatchRequest->videoLinkIdentifier(),
            'wiki_id' => (string) $videoWatchRequest->wikiIdentifier(),
            'principal_id' => (string) $videoWatchRequest->principalIdentifier(),
            'sent_at' => $videoWatchRequest->sentAt(),
        ]);
    }

    public function findByTranslationSetAndPrincipal(TranslationSetIdentifier $translationSetIdentifier, PrincipalIdentifier $principalIdentifier, DateTimeImmutable $after): array
    {
        return array_values(VideoWatchRequestModel::query()->whereIn('wiki_id', WikiModel::query()->select('id')->where('translation_set_identifier', (string) $translationSetIdentifier))->where('principal_id', (string) $principalIdentifier)->where('sent_at', '>', $after)->orderBy('sent_at')->orderBy('id')->get()->map(fn (VideoWatchRequestModel $model): VideoWatchRequest => $this->restore($model))->all());
    }

    public function findLatestByVideoLink(VideoLinkIdentifier $videoLinkIdentifier): ?VideoWatchRequest
    {
        return $this->findLatest($videoLinkIdentifier);
    }

    public function findLatestByVideoLinkAndPrincipal(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): ?VideoWatchRequest
    {
        return $this->findLatest($videoLinkIdentifier, $principalIdentifier);
    }

    private function findLatest(VideoLinkIdentifier $videoLinkIdentifier, ?PrincipalIdentifier $principalIdentifier = null): ?VideoWatchRequest
    {
        $model = VideoWatchRequestModel::query()->where('video_link_id', (string) $videoLinkIdentifier)->when($principalIdentifier !== null, static fn ($query) => $query->where('principal_id', (string) $principalIdentifier))->orderByDesc('sent_at')->orderBy('id')->first();

        return $model === null ? null : $this->restore($model);
    }

    private function restore(VideoWatchRequestModel $model): VideoWatchRequest
    {
        return new VideoWatchRequest(new VideoWatchRequestIdentifier($model->id), new VideoLinkIdentifier($model->video_link_id), new WikiIdentifier($model->wiki_id), new PrincipalIdentifier($model->principal_id), $model->sent_at->toDateTimeImmutable());
    }
}
