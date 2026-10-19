<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Tests\Helper\StrTestHelper;

class PrincipalGroupMemberReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $principalIdentifier = StrTestHelper::generateUuid();
        $identityIdentifier = StrTestHelper::generateUuid();

        $readModel = new PrincipalGroupMemberReadModel($principalIdentifier, $identityIdentifier, 'alice', 'alice@example.com', 'forced_stop');

        $this->assertSame($principalIdentifier, $readModel->principalIdentifier());
        $this->assertSame($identityIdentifier, $readModel->identityIdentifier());
        $this->assertSame('alice', $readModel->identityName());
        $this->assertSame('alice@example.com', $readModel->email());
        $this->assertSame([
            'status' => 'forced_stop',
            'principalIdentifier' => $principalIdentifier,
            'identityIdentifier' => $identityIdentifier,
            'identityName' => 'alice',
            'email' => 'alice@example.com',
        ], $readModel->toArray());
    }
}
