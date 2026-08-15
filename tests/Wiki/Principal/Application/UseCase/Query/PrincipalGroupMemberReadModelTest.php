<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Tests\Helper\StrTestHelper;

class PrincipalGroupMemberReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $principalIdentifier = StrTestHelper::generateUuid();
        $identityIdentifier = StrTestHelper::generateUuid();

        $readModel = new PrincipalGroupMemberReadModel($principalIdentifier, $identityIdentifier, 'alice', 'alice@example.com');

        $this->assertSame($principalIdentifier, $readModel->principalIdentifier());
        $this->assertSame($identityIdentifier, $readModel->identityIdentifier());
        $this->assertSame('alice', $readModel->identityName());
        $this->assertSame('alice@example.com', $readModel->email());
        $this->assertSame([
            'principalIdentifier' => $principalIdentifier,
            'identityIdentifier' => $identityIdentifier,
            'identityName' => 'alice',
            'email' => 'alice@example.com',
        ], $readModel->toArray());
    }
}
