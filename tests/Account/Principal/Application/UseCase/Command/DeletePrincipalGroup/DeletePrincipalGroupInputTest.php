<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInput;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;

class DeletePrincipalGroupInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new DeletePrincipalGroupInput($principalGroupIdentifier);

        $this->assertSame($principalGroupIdentifier, $subject->principalGroupIdentifier());
    }
}
