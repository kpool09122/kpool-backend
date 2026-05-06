<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface GetGroupWikiInputPort
{
    public function slug(): Slug;

    public function language(): Language;
}
