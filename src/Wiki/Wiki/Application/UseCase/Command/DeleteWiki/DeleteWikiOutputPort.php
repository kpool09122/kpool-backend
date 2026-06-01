<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki;

interface DeleteWikiOutputPort
{
    /** @return array{} */
    public function toArray(): array;
}
