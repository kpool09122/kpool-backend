<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface GetMyAgencyDraftWikiInputPort
{
    public function slug(): Slug;

    public function language(): Language;

    public function editorIdentifier(): PrincipalIdentifier;
}
