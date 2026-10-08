<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByPrincipal\ListContactsByPrincipalInput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ListContactsByPrincipalInputTest extends TestCase
{
    public function testRequesterAndTargetIdentifiersArePreservedSeparately(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsByPrincipalInput($principalIdentifier, $targetPrincipalIdentifier);

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($targetPrincipalIdentifier, $input->targetPrincipalIdentifier());
    }
}
