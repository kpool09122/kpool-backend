<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ProvisionPrincipalInputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new ProvisionPrincipalInput($identity);
        $this->assertSame($identity, $input->identityIdentifier());
    }
}
