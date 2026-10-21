<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;

class ListVideosOutput implements ListVideosOutputPort
{
    /** @var list<VideoLinkReadModel> */
    private array $videos = [];

    /** @param list<VideoLinkReadModel> $videos */
    public function output(array $videos): void
    {
        $this->videos = $videos;
    }

    /** @return array{videos: list<array{videoLinkIdentifier:string,url:string,title:string,thumbnailUrl:?string,publishedAt:?string,displayOrder:int,videoUsage:string}>} */
    public function toArray(): array
    {
        return ['videos' => array_map(static fn (VideoLinkReadModel $video): array => $video->toArray(), $this->videos)];
    }
}
