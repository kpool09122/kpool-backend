<?php

namespace Businesses\Wiki\Member\Domain\Factory;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;

interface MemberFactoryInterface
{
    public function create(
        Translation $translation,
        MemberName $name,
    ): Member;
}
