<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Source\Wiki\Playlist\Domain\Entity\Playlist;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

class PlaylistTestHelper
{
    /** @param list<PlaylistItem> $items */
    public static function playlist(?PlaylistIdentifier $id = null, ?PrincipalIdentifier $owner = null, array $items = []): Playlist
    {
        $at = new DateTimeImmutable('2026-10-26T00:00:00+00:00');

        return new Playlist($id ?? new PlaylistIdentifier(StrTestHelper::generateUuid()), $owner ?? new PrincipalIdentifier(StrTestHelper::generateUuid()), new PlaylistName('My playlist'), $at, $at, $items);
    }

    public static function item(PlaylistIdentifier $id, int $position, ?VideoLinkIdentifier $source = null, ?string $externalId = null): PlaylistItem
    {
        $video = $externalId ?? str_pad((string) $position, 11, '0', STR_PAD_LEFT);

        return new PlaylistItem($id, $source, VideoProvider::YOUTUBE, new ExternalVideoId($video), new YouTubeVideoUrl('https://www.youtube.com/watch?v=' . $video), new VideoTitle('Snapshot title'), new ThumbnailUrl('https://i.ytimg.com/vi/' . $video . '/hqdefault.jpg'), new DurationSeconds(120), new PlaylistItemPosition($position));
    }
}
