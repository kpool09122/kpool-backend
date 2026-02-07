<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Domain\Entity\Wiki;

interface TranslationServiceInterface
{
    public function translateWiki(Wiki $wiki, Language $targetLanguage): TranslatedWikiData;
}
