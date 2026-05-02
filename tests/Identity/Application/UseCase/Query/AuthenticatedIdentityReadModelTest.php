<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use Source\Identity\Application\UseCase\Query\AuthenticatedIdentityReadModel;
use Tests\TestCase;

class AuthenticatedIdentityReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $readModel = new AuthenticatedIdentityReadModel(
            identityIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5fe',
            username: 'test-user',
            email: 'test@example.com',
            language: 'ja',
            profileImage: '/storage/profile/test.png',
        );

        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5fe', $readModel->identityIdentifier());
        $this->assertSame('test-user', $readModel->username());
        $this->assertSame('test@example.com', $readModel->email());
        $this->assertSame('ja', $readModel->language());
        $this->assertSame('/storage/profile/test.png', $readModel->profileImage());
        $this->assertSame([
            'identityIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5fe',
            'username' => 'test-user',
            'email' => 'test@example.com',
            'language' => 'ja',
            'profileImage' => '/storage/profile/test.png',
        ], $readModel->toArray());
    }
}
