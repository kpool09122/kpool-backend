<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Event;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\Event\DelegationRevoked;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;

class DelegationRevokedTest extends TestCase
{
    public function test__construct(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $revokedAt = new DateTimeImmutable();

        $event = new DelegationRevoked(
            $delegationIdentifier,
            $revokedAt,
        );

        $this->assertSame($delegationIdentifier, $event->delegationIdentifier());
        $this->assertSame($revokedAt, $event->revokedAt());
    }
}
