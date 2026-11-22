<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalStatusUpdate;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;

class PrincipalStatusUpdateTest extends TestCase
{
    public function testPreservesInstances(): void
    {
        $id = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $subject = new PrincipalStatusUpdate($id, PrincipalStatus::SUSPENDED);
        self::assertSame($id, $subject->principalIdentifier);
        self::assertSame(PrincipalStatus::SUSPENDED, $subject->status);
    }
}
