<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use PHPUnit\Framework\TestCase;
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
        $output->output([new VideoLinkReadModel('id', 'https://www.youtube.com/watch?v=test', 'title', 'https://i.ytimg.com/test.jpg', '2026-01-01T00:00:00+00:00', 'music_video', 253, 'creator-id', '作成者')]);
        $this->assertSame(['videos' => [['videoLinkIdentifier' => 'id','url' => 'https://www.youtube.com/watch?v=test','title' => 'title','thumbnailUrl' => 'https://i.ytimg.com/test.jpg','publishedAt' => '2026-01-01T00:00:00+00:00','videoUsage' => 'music_video','durationSeconds' => 253, 'creatorPrincipalIdentifier' => 'creator-id', 'creatorIdentityName' => '作成者']]], $output->toArray());
        $output->output([]);
        $this->assertSame(['videos' => []], $output->toArray());
    }
}
