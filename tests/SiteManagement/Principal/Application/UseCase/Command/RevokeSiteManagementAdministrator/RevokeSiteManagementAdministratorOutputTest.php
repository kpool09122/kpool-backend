<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorOutput;

class RevokeSiteManagementAdministratorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new RevokeSiteManagementAdministratorOutput())->toArray());
    }
}
