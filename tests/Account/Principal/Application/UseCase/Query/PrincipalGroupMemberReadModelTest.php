<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;

class PrincipalGroupMemberReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new PrincipalGroupMemberReadModel('principalIdentifier-value', 'identityIdentifier-value', 'identityName-value', 'user@example.com');
        $this->assertSame([
            'principalIdentifier' => 'principalIdentifier-value',
            'identityIdentifier' => 'identityIdentifier-value',
            'identityName' => 'identityName-value',
            'email' => 'user@example.com',
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new PrincipalGroupMemberReadModel('principalIdentifier-value', 'identityIdentifier-value', 'identityName-value', 'user@example.com');
        $this->assertSame([
            'principalIdentifier' => 'principalIdentifier-value',
            'identityIdentifier' => 'identityIdentifier-value',
            'identityName' => 'identityName-value',
            'email' => 'user@example.com',
        ], $subject->toArray());
    }
}
