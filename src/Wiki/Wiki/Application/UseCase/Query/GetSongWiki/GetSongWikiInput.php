<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetSongWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

readonly class GetSongWikiInput implements GetSongWikiInputPort
{
    public function __construct(
        private Slug $slug,
        private Language $language,
    ) {
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
