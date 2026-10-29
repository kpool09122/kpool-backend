<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Repository;

use Application\Models\Wiki\VideoLinkWatch as VideoLinkWatchModel;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkWatch;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkWatchRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkWatchIdentifier;

final readonly class VideoLinkWatchRepository implements VideoLinkWatchRepositoryInterface
{
    public function findByVideoLinkAndOwnerAccount(VideoLinkIdentifier $videoLinkIdentifier, AccountIdentifier $ownerAccountIdentifier): ?VideoLinkWatch
    {
        $model = VideoLinkWatchModel::query()->where('video_link_id', (string) $videoLinkIdentifier)->where('owner_account_id', (string) $ownerAccountIdentifier)->first();

        return $model === null ? null : new VideoLinkWatch(
            new VideoLinkWatchIdentifier($model->id),
            new VideoLinkIdentifier($model->video_link_id),
            new AccountIdentifier($model->owner_account_id),
            new PrincipalIdentifier($model->principal_id),
            $model->watched_at->toDateTimeImmutable(),
        );
    }

    public function save(VideoLinkWatch $videoLinkWatch): void
    {
        VideoLinkWatchModel::query()->firstOrCreate(
            [
                'video_link_id' => (string) $videoLinkWatch->videoLinkIdentifier(),
                'owner_account_id' => (string) $videoLinkWatch->ownerAccountIdentifier(),
            ],
            [
                'id' => (string) $videoLinkWatch->videoLinkWatchIdentifier(),
                'principal_id' => (string) $videoLinkWatch->principalIdentifier(),
                'watched_at' => $videoLinkWatch->watchedAt(),
            ],
        );
    }
}
