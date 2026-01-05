<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Event;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Event\DelegatedIdentityDeleted;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;

class DelegatedIdentityDeletedTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $deletedAt = new DateTimeImmutable();

        $event = new DelegatedIdentityDeleted(
            $delegationIdentifier,
            $deletedAt,
        );

        $this->assertSame($delegationIdentifier, $event->delegationIdentifier());
        $this->assertSame($deletedAt, $event->deletedAt());
    }
}
