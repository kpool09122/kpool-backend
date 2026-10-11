<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorOutput;

class RevokeSiteManagementOperatorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new RevokeSiteManagementOperatorOutput())->toArray());
    }
}
