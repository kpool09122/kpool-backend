<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles;

use Source\Wiki\Wiki\Application\UseCase\Query\RelatedProfileReadModel;

class ListRelatedProfilesOutput implements ListRelatedProfilesOutputPort
{
    /** @var list<RelatedProfileReadModel> */
    private array $profiles = [];

    /**
     * @param list<RelatedProfileReadModel> $profiles
     */
    public function output(array $profiles): void
    {
        $this->profiles = $profiles;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'profiles' => array_map(static fn (RelatedProfileReadModel $profile): array => $profile->toArray(), $this->profiles),
        ];
    }
}
