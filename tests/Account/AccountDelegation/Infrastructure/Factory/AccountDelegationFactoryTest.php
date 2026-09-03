<?php

declare(strict_types=1);

namespace Tests\Account\AccountDelegation\Infrastructure\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\AccountDelegation\Infrastructure\Factory\AccountDelegationFactory;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountDelegationFactoryTest extends TestCase
{
    /** @return array<string, array{bool, DelegationDirection}> */
    public static function directionProvider(): array
    {
        return [
            'agency requests' => [true, DelegationDirection::FROM_AGENCY],
            'talent requests' => [false, DelegationDirection::FROM_TALENT],
        ];
    }

    #[DataProvider('directionProvider')]
    public function testCreatesPendingAccountDelegation(bool $requestedByAgency, DelegationDirection $expectedDirection): void
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agency,
            $talent,
            $agency,
            AffiliationStatus::ACTIVE,
            null,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            null,
        );

        $delegation = (new AccountDelegationFactory())->create($affiliation, $requestedByAgency ? $agency : $talent);

        $this->assertSame((string) $agency, (string) $delegation->delegateAccountIdentifier());
        $this->assertSame((string) $talent, (string) $delegation->delegatorAccountIdentifier());
        $this->assertSame($expectedDirection, $delegation->direction());
        $this->assertSame(DelegationStatus::PENDING, $delegation->status());
        $this->assertNull($delegation->approvedAt());
        $this->assertNull($delegation->revokedAt());
    }
}
