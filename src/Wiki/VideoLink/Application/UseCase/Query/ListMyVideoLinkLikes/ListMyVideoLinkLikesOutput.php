<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;

class ListMyVideoLinkLikesOutput implements ListMyVideoLinkLikesOutputPort
{
    /** @var list<string> */
    private array $likedVideoLinkIdentifiers = [];

    /** @var list<LikedVideoLinkReadModel> */
    private array $videos = [];

    /** @var array{currentPage:int,perPage:int,total:int}|null */
    private ?array $pagination = null;

    /**
     * @param list<string> $likedVideoLinkIdentifiers
     * @param list<LikedVideoLinkReadModel> $videos
     * @param array{currentPage:int,perPage:int,total:int}|null $pagination
     */
    public function output(array $likedVideoLinkIdentifiers, array $videos, ?array $pagination = null): void
    {
        $this->likedVideoLinkIdentifiers = $likedVideoLinkIdentifiers;
        $this->videos = $videos;
        $this->pagination = $pagination;
    }

    /** @return array{likedVideoLinkIdentifiers:list<string>,videos:list<array{provider:string,externalVideoId:string,videoLinkIdentifier:string,url:string,title:string,thumbnailUrl:?string,durationSeconds:?int,language:string,resourceType:string,slug:string,likedAt:string,playlists:list<array{playlistIdentifier:string,name:string}>,isOfficialOwnerLike:bool}>,pagination:array{currentPage:int,perPage:int,total:int}|null} */
    public function toArray(): array
    {
        return [
            'likedVideoLinkIdentifiers' => $this->likedVideoLinkIdentifiers,
            'videos' => array_map(static fn (LikedVideoLinkReadModel $video): array => $video->toArray(), $this->videos),
            'pagination' => $this->pagination,
        ];
    }
}
