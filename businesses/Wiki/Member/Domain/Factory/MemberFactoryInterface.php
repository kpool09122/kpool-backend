<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\Domain\Factory;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;

interface MemberFactoryInterface
{
    /**
     * @param Translation $translation
     * @param MemberName $name
     * @return Member
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        Translation $translation,
        MemberName $name,
    ): Member;
}
