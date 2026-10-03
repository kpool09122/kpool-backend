<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupInput;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class AddPrincipalToPrincipalGroupInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $principalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new AddPrincipalToPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->assertSame($principalGroupIdentifier, $subject->principalGroupIdentifier());
        $this->assertSame($principalIdentifier, $subject->principalIdentifier());
    }
}
