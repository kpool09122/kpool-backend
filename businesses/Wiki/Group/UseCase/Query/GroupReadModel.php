<?php

namespace Businesses\Wiki\Group\UseCase\Query;

readonly class GroupReadModel
{
    /**
     * @param string $groupId
     * @param string $name
     * @param string $companyName
     * @param string $description
     * @param string $imageUrl
     * @param list<SongReadModel> $songReadModels
     */
    public function __construct(
        private string $groupId,
        private string $name,
        private string $companyName,
        private string $description,
        private string $imageUrl,
        private array $songReadModels,
    ) {
    }

    public function groupId(): string
    {
        return $this->groupId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function companyName(): string
    {
        return $this->companyName;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function imageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @return list<SongReadModel>
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
            'group_id' => $this->groupId,
            'name' => $this->name,
            'company_name' => $this->companyName,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'songs' => array_map(static fn (SongReadModel $songReadModel) => $songReadModel->toArray(), $this->songReadModels),
        ];
    }
}
