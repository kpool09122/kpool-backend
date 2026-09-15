<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountReferenceReadModel;

class AuthenticatedAccountReferenceReadModelTest extends TestCase
{
    public function testToArrayReturnsAccountReference(): void
    {
        $readModel = new AuthenticatedAccountReferenceReadModel(
            accountIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5aa',
            name: 'Original Account',
        );

        $this->assertSame([
            'accountIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5aa',
            'name' => 'Original Account',
        ], $readModel->toArray());
    }
}
