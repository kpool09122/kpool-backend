<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles;

use Source\Wiki\Wiki\Application\UseCase\Query\RelatedProfileReadModel;

interface ListRelatedProfilesOutputPort
{
    /**
     * @param list<RelatedProfileReadModel> $profiles
     */
    public function output(array $profiles): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
