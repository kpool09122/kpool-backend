<?php

namespace Businesses\Member\UseCase\Query;

use DateTimeImmutable;

readonly class MemberReadModel
{
    /**
     * @param string $memberId
     * @param string $name
     * @param string $groupName
     * @param DateTimeImmutable $birthday
     * @param string $career
     * @param string $imageUrl
     * @param list<SongReadModel> $songReadModels
     */
    public function __construct(
        private string $memberId,
        private string $name,
        private string $groupName,
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

    public function groupName(): string
    {
        return $this->groupName;
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
            'group_name' => $this->groupName,
            'birthday' => $this->birthday,
            'career' => $this->career,
            'image_url' => $this->imageUrl,
            'songs' => array_map(static fn (SongReadModel $songReadModel) => $songReadModel->toArray(), $this->songReadModels),
        ];
    }
}
