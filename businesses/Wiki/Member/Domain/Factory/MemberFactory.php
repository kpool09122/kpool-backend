<?php

namespace Businesses\Wiki\Member\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;
use Businesses\Wiki\Member\Domain\ValueObject\RealName;
use Businesses\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;

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
