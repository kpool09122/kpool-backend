<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;

class VideoLinkReadModelTest extends TestCase
{
    public function testPreservesNullMetadata(): void
    {
        $model = new VideoLinkReadModel('id', 'url', 'title', null, null, 1, 'youtube_auto_view_count');
        $this->assertSame(['videoLinkIdentifier' => 'id','url' => 'url','title' => 'title','thumbnailUrl' => null,'publishedAt' => null,'displayOrder' => 1,'videoUsage' => 'youtube_auto_view_count'], $model->toArray());
    }
}
