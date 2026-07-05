<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use Source\Identity\Application\UseCase\Query\IdentityProfileReadModel;
use Tests\TestCase;

class IdentityProfileReadModelTest extends TestCase
{
    public function testToArrayDoesNotIncludeSocialConnections(): void
    {
        $readModel = new IdentityProfileReadModel(
            identityIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5fe',
            identityName: 'profile-owner',
            language: 'ja',
            profileImage: 'http://127.0.0.1:8080/storage/profile/test.png',
        );

        $payload = $readModel->toArray();

        $this->assertSame([
            'identityIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5fe',
            'identityName' => 'profile-owner',
            'language' => 'ja',
            'profileImage' => 'http://127.0.0.1:8080/storage/profile/test.png',
        ], $payload);
        $this->assertArrayNotHasKey('socialConnections', $payload);
    }
}
