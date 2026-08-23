<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

interface ListRelatedWikisInterface
{
    /**
     * @throws \Source\Wiki\Shared\Domain\Exception\DisallowedException
     * @throws \Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException
     * @throws \Source\Wiki\Wiki\Application\Exception\WikiNotFoundException
     */
    public function process(ListRelatedWikisInputPort $input, ListRelatedWikisOutputPort $output): void;
}
