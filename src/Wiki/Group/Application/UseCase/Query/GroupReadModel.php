<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query;

readonly class GroupReadModel
{
    /**
     * @param string $groupId
     * @param string $name
     * @param string $agencyName
     * @param string $description
     * @param string $imageUrl
     * @param list<SongReadModel> $songReadModels
     */
    public function __construct(
        private string $groupId,
        private string $name,
        private string $agencyName,
        private string $description,
        private string $imageUrl,
        private array  $songReadModels,
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

    public function agencyName(): string
    {
        return $this->agencyName;
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
            'company_name' => $this->agencyName,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'songs' => array_map(static fn (SongReadModel $songReadModel) => $songReadModel->toArray(), $this->songReadModels),
        ];
    }
}
