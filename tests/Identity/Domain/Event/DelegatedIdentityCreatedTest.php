<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Event;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class DelegatedIdentityCreatedTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $event = new DelegatedIdentityCreated(
            $delegationIdentifier,
            $delegatedIdentityIdentifier,
            $originalIdentityIdentifier,
            $createdAt,
        );

        $this->assertSame($delegationIdentifier, $event->delegationIdentifier());
        $this->assertSame($delegatedIdentityIdentifier, $event->delegatedIdentityIdentifier());
        $this->assertSame($originalIdentityIdentifier, $event->originalIdentityIdentifier());
        $this->assertSame($createdAt, $event->createdAt());
    }
}
