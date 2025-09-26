<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class DraftGroupFactory implements DraftGroupFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        GroupName $name,
    ): DraftGroup {
        return new DraftGroup(
            new GroupIdentifier($this->ulidGenerator->generate()),
            null,
            $editorIdentifier,
            $translation,
            $name,
            null,
            new Description(''),
            [],
            null,
            ApprovalStatus::Pending,
        );
    }
}
