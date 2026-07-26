<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Application\UseCase\Query\MemberPrincipalGroupReadModel;
use Tests\Helper\StrTestHelper;

class MemberPrincipalGroupReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $principalGroupIdentifier = StrTestHelper::generateUuid();
        $readModel = new MemberPrincipalGroupReadModel(
            principalGroupIdentifier: $principalGroupIdentifier,
            name: 'Administrators',
            isDefault: true,
        );

        $this->assertSame([
            'principalGroupIdentifier' => $principalGroupIdentifier,
            'name' => 'Administrators',
            'isDefault' => true,
        ], $readModel->toArray());
    }
}
