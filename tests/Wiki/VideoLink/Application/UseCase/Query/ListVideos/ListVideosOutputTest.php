<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutput;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;

class ListVideosOutputTest extends TestCase
{
    public function testSerializesEmptyList(): void
    {
        $this->assertSame(['videos' => []], (new ListVideosOutput())->toArray());
    }

    public function testSerializesAllFieldsAndReplacesPriorOutput(): void
    {
        $output = new ListVideosOutput();
        $output->output([new VideoLinkReadModel('id', new YouTubeVideoUrl('https://www.youtube.com/watch?v=abcdefghijk'), 'title', 'https://i.ytimg.com/test.jpg', '2026-01-01T00:00:00+00:00', 'music_video', 253, 'creator-id', '作成者', 2, false, true)]);
        $this->assertSame(['videos' => [['provider' => 'youtube', 'externalVideoId' => 'abcdefghijk', 'videoLinkIdentifier' => 'id','url' => 'https://www.youtube.com/watch?v=abcdefghijk','title' => 'title','thumbnailUrl' => 'https://i.ytimg.com/test.jpg','publishedAt' => '2026-01-01T00:00:00+00:00','videoUsage' => 'music_video','durationSeconds' => 253, 'creatorPrincipalIdentifier' => 'creator-id', 'creatorIdentityName' => '作成者', 'likeCount' => 2, 'hasOfficialOwnerLike' => false, 'hasOfficialOwnerWatch' => true]]], $output->toArray());
        $output->output([]);
        $this->assertSame(['videos' => []], $output->toArray());
    }
}
