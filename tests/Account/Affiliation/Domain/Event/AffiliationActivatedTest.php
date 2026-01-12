<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Domain\Event;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class AffiliationActivatedTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $activatedAt = new DateTimeImmutable();

        $event = new AffiliationActivated(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $activatedAt,
        );

        $this->assertSame($affiliationIdentifier, $event->affiliationIdentifier());
        $this->assertSame($agencyAccountIdentifier, $event->agencyAccountIdentifier());
        $this->assertSame($talentAccountIdentifier, $event->talentAccountIdentifier());
        $this->assertSame($activatedAt, $event->activatedAt());
    }
}
