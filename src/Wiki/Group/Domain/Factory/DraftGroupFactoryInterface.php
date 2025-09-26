<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

interface DraftGroupFactoryInterface
{
    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        GroupName $name,
    ): DraftGroup;
}
