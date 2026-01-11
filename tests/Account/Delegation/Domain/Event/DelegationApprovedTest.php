<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Domain\Event;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Domain\Event\DelegationApproved;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class DelegationApprovedTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegateIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $approvedAt = new DateTimeImmutable();

        $event = new DelegationApproved(
            $delegationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            $approvedAt,
        );

        $this->assertSame($delegationIdentifier, $event->delegationIdentifier());
        $this->assertSame($delegateIdentifier, $event->delegateIdentifier());
        $this->assertSame($delegatorIdentifier, $event->delegatorIdentifier());
        $this->assertSame($approvedAt, $event->approvedAt());
    }
}
