<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorOutput;

class GrantSiteManagementAdministratorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new GrantSiteManagementAdministratorOutput())->toArray());
    }
}
