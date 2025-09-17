<?php

namespace Businesses\Wiki\Member\UseCase\Query;

use DateTimeImmutable;

readonly class MemberReadModel
{
    /**
     * @param string $memberId
     * @param string $name
     * @param string $realName
     * @param string[] $groupNames
     * @param DateTimeImmutable $birthday
     * @param string $career
     * @param string $imageUrl
     * @param list<SongReadModel> $songReadModels
     */
    public function __construct(
        private string $memberId,
        private string $name,
        private string $realName,
        private array $groupNames,
        private DateTimeImmutable $birthday,
        private string $career,
        private string $imageUrl,
        private array $songReadModels,
    ) {
    }

    public function memberId(): string
    {
        return $this->memberId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function realName(): string
    {
        return $this->realName;
    }

    /**
     * @return string[]
     */
    public function groupNames(): array
    {
        return $this->groupNames;
    }

    public function birthday(): DateTimeImmutable
    {
        return $this->birthday;
    }

    public function career(): string
    {
        return $this->career;
    }

    public function imageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @return SongReadModel[]
     */
    public function songReadModels(): array
    {
        return $this->songReadModels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'name' => $this->name,
            'real_name' => $this->realName,
            'group_name' => $this->groupNames,
            'birthday' => $this->birthday,
            'career' => $this->career,
            'image_url' => $this->imageUrl,
            'songs' => array_map(static fn (SongReadModel $songReadModel) => $songReadModel->toArray(), $this->songReadModels),
        ];
    }
}
