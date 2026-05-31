<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles;

use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

interface ListRelatedProfilesInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(ListRelatedProfilesInputPort $input, ListRelatedProfilesOutputPort $output): void;
}
