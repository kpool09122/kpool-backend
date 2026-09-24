<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use Source\Identity\Application\UseCase\Query\PasskeyReadModel;
use Tests\TestCase;

class PasskeyReadModelTest extends TestCase
{
    public function testItSerializesOnlyPublicPasskeyMetadata(): void
    {
        $readModel = new PasskeyReadModel(
            passkeyIdentifier: '123e4567-e89b-72d3-a456-426614174001',
            displayName: 'MacBook Touch ID',
            transports: ['internal', 'hybrid'],
            backupEligible: true,
            backupState: false,
            lastUsedAt: '2026-09-23T01:02:03+00:00',
            createdAt: '2026-09-20T04:05:06+00:00',
        );

        $this->assertSame([
            'passkeyIdentifier' => '123e4567-e89b-72d3-a456-426614174001',
            'displayName' => 'MacBook Touch ID',
            'transports' => ['internal', 'hybrid'],
            'backupEligible' => true,
            'backupState' => false,
            'lastUsedAt' => '2026-09-23T01:02:03+00:00',
            'createdAt' => '2026-09-20T04:05:06+00:00',
        ], $readModel->toArray());
    }
}
