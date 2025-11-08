<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateAgencyInputTest extends TestCase
{
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateAgencyInput($agencyIdentifier, $publishedAgencyIdentifier, $principal);
        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame((string)$publishedAgencyIdentifier, (string)$input->publishedAgencyIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
