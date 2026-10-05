<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ProvisionPrincipalOutputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $output = new ProvisionPrincipalOutput();
        $this->assertSame([], $output->toArray());
        $this->assertNull($output->principal());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()));
        $output->setPrincipal($principal);
        $this->assertSame($principal, $output->principal());
        $this->assertSame(['principalIdentifier' => (string) $principal->principalIdentifier(), 'identityIdentifier' => (string) $principal->identityIdentifier()], $output->toArray());
    }
}
