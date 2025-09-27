<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\ValueObject\Career;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Member\Domain\ValueObject\RealName;
use Source\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class DraftMemberFactory implements DraftMemberFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
     * @param MemberName $name
     * @return DraftMember
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        MemberName $name,
    ): DraftMember {
        return new DraftMember(
            new MemberIdentifier($this->ulidGenerator->generate()),
            null,
            $editorIdentifier,
            $translation,
            $name,
            new RealName(''),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Pending,
        );
    }
}
