<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;

class VideoLinkReadModelTest extends TestCase
{
    public function testPreservesNullMetadata(): void
    {
        $model = new VideoLinkReadModel('id', 'url', 'title', null, null, 'youtube_auto_view_count', null, null, null);
        $this->assertSame(['videoLinkIdentifier' => 'id','url' => 'url','title' => 'title','thumbnailUrl' => null,'publishedAt' => null,'videoUsage' => 'youtube_auto_view_count','durationSeconds' => null,'creatorPrincipalIdentifier' => null,'creatorIdentityName' => null], $model->toArray());
    }

    public function testReturnsManualCreator(): void
    {
        $model = new VideoLinkReadModel('id', 'url', 'title', null, null, 'music_video', 253, 'creator-id', '作成者');
        $this->assertSame('作成者', $model->toArray()['creatorIdentityName']);
        $this->assertSame('creator-id', $model->creatorPrincipalIdentifier);
        $this->assertSame('creator-id', $model->toArray()['creatorPrincipalIdentifier']);
    }
}
