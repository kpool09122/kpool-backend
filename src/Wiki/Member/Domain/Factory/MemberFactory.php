<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\ValueObject\Career;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Member\Domain\ValueObject\RealName;
use Source\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;

readonly class MemberFactory implements MemberFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param Translation $translation
     * @param MemberName $name
     * @return Member
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        Translation $translation,
        MemberName $name,
    ): Member {
        return new Member(
            new MemberIdentifier($this->ulidGenerator->generate()),
            $translation,
            $name,
            new RealName(''),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
        );
    }
}
