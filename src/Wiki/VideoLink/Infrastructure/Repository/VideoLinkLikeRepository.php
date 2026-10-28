<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Repository;

use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkLike;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkLikeRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkLikeIdentifier;

readonly class VideoLinkLikeRepository implements VideoLinkLikeRepositoryInterface
{
    public function findByVideoLinkAndPrincipal(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): ?VideoLinkLike
    {
        $model = VideoLinkLikeModel::query()->where('video_link_id', (string) $videoLinkIdentifier)->where('principal_id', (string) $principalIdentifier)->first();

        return $model === null ? null : new VideoLinkLike(
            new VideoLinkLikeIdentifier($model->id),
            new VideoLinkIdentifier($model->video_link_id),
            new PrincipalIdentifier($model->principal_id),
            $model->created_at->toDateTimeImmutable(),
            $model->is_official_owner,
        );
    }

    public function save(VideoLinkLike $videoLinkLike): void
    {
        VideoLinkLikeModel::query()->updateOrCreate(
            ['id' => (string) $videoLinkLike->videoLinkLikeIdentifier()],
            [
                'video_link_id' => (string) $videoLinkLike->videoLinkIdentifier(),
                'principal_id' => (string) $videoLinkLike->principalIdentifier(),
                'created_at' => $videoLinkLike->createdAt(),
                'is_official_owner' => $videoLinkLike->isOfficialOwner(),
            ],
        );
    }

    public function deleteByVideoLinkAndPrincipal(VideoLinkIdentifier $videoLinkIdentifier, PrincipalIdentifier $principalIdentifier): void
    {
        VideoLinkLikeModel::query()->where('video_link_id', (string) $videoLinkIdentifier)->where('principal_id', (string) $principalIdentifier)->delete();
    }
}
