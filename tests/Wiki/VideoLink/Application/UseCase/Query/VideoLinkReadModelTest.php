<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;

class VideoLinkReadModelTest extends TestCase
{
    public function testPreservesNullMetadata(): void
    {
        $model = new VideoLinkReadModel('id', new YouTubeVideoUrl('https://www.youtube.com/watch?v=abcdefghijk'), 'title', null, null, 'youtube_auto_view_count', null, null, null, 0);
        $this->assertSame(['provider' => 'youtube', 'externalVideoId' => 'abcdefghijk', 'videoLinkIdentifier' => 'id','url' => 'https://www.youtube.com/watch?v=abcdefghijk','title' => 'title','thumbnailUrl' => null,'publishedAt' => null,'videoUsage' => 'youtube_auto_view_count','durationSeconds' => null,'creatorPrincipalIdentifier' => null,'creatorIdentityName' => null,'likeCount' => 0], $model->toArray());
    }

    public function testReturnsManualCreator(): void
    {
        $model = new VideoLinkReadModel('id', new YouTubeVideoUrl('https://www.youtube.com/watch?v=abcdefghijk'), 'title', null, null, 'music_video', 253, 'creator-id', '作成者', 3);
        $this->assertSame(3, $model->toArray()['likeCount']);
        $this->assertSame('作成者', $model->toArray()['creatorIdentityName']);
        $this->assertSame('creator-id', $model->creatorPrincipalIdentifier);
        $this->assertSame('creator-id', $model->toArray()['creatorPrincipalIdentifier']);
    }

    public function testIdentifiesTikTokVideo(): void
    {
        $url = 'https://www.tiktok.com/@scout2015/video/6718335390845095173';
        $model = new VideoLinkReadModel('id', new TikTokVideoUrl($url), 'title', null, null, 'short', null, null, null, 0);
        self::assertSame('tiktok', $model->provider);
        self::assertSame('6718335390845095173', $model->externalVideoId);
    }

    public function testRejectsUnresolvedTikTokUrl(): void
    {
        $url = 'https://vm.tiktok.com/abc/';
        $this->expectException(InvalidArgumentException::class);
        new VideoLinkReadModel('id', new TikTokVideoUrl($url), 'title', null, null, 'short', null, null, null, 0);
    }
}
