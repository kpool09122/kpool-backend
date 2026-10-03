<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountSummaryReadModel;

class AuthenticatedAccountSummaryReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new AuthenticatedAccountSummaryReadModel('accountIdentifier-value', 'user@example.com', 'type-value', 'name-value', 'status-value', 'accountCategory-value', 'phone-value', ['locality' => 'Tokyo']);
        $this->assertSame([
            'accountIdentifier' => 'accountIdentifier-value',
            'email' => 'user@example.com',
            'type' => 'type-value',
            'name' => 'name-value',
            'status' => 'status-value',
            'accountCategory' => 'accountCategory-value',
            'phone' => 'phone-value',
            'address' => ['locality' => 'Tokyo'],
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new AuthenticatedAccountSummaryReadModel('accountIdentifier-value', 'user@example.com', null, 'name-value', 'status-value', 'accountCategory-value', null, null);
        $this->assertSame([
            'accountIdentifier' => 'accountIdentifier-value',
            'email' => 'user@example.com',
            'type' => null,
            'name' => 'name-value',
            'status' => 'status-value',
            'accountCategory' => 'accountCategory-value',
            'phone' => null,
            'address' => null,
        ], $subject->toArray());
    }
}
