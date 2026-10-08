<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ProvisionPrincipalInputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new ProvisionPrincipalInput($identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        $this->assertSame($identity, $input->identityIdentifier());
    }
}
