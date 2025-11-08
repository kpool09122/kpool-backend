<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Application\UseCase\Command\RejectAgency\RejectAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectAgencyInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new RejectAgencyInput(
            $agencyIdentifier,
            $principal,
        );

        $this->assertSame((string)$agencyIdentifier, (string)$input->agencyIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
