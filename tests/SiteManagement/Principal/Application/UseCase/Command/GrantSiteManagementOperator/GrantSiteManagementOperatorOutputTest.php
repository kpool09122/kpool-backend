<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementOperator\GrantSiteManagementOperatorOutput;

class GrantSiteManagementOperatorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new GrantSiteManagementOperatorOutput())->toArray());
    }
}
