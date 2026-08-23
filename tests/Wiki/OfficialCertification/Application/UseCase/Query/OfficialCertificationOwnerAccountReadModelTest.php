<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Query;

use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationOwnerAccountReadModel;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class OfficialCertificationOwnerAccountReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $accountIdentifier = StrTestHelper::generateUuid();
        $readModel = new OfficialCertificationOwnerAccountReadModel(
            accountIdentifier: $accountIdentifier,
            email: 'owner@example.com',
            type: 'corporate',
            name: 'Owner Account',
            status: 'active',
            category: 'talent',
        );

        $this->assertSame([
            'accountIdentifier' => $accountIdentifier,
            'email' => 'owner@example.com',
            'type' => 'corporate',
            'name' => 'Owner Account',
            'status' => 'active',
            'category' => 'talent',
        ], $readModel->toArray());
    }
}
