<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalOutputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $output = new CreatePrincipalOutput();
        $this->assertSame([], $output->toArray());
        $this->assertNull($output->principal());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier('00000000-0000-7000-8000-000000000009'), PrincipalStatus::ACTIVE);
        $output->setPrincipal($principal);
        $this->assertSame($principal, $output->principal());
        $this->assertSame(['principalIdentifier' => (string) $principal->principalIdentifier(), 'identityIdentifier' => (string) $principal->identityIdentifier()], $output->toArray());
    }
}
