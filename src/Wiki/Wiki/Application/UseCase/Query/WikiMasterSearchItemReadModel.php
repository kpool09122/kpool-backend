<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class WikiMasterSearchItemReadModel
{
    public function __construct(
        private string $id,
        private string $name,
        private string $slug,
        private string $resourceType,
    ) {
    }

    /**
     * @return array{id: string, name: string, slug: string, resourceType: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'resourceType' => $this->resourceType,
        ];
    }
}
