<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\Affiliation\Domain\Factory\AffiliationFactoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Affiliation\Infrastructure\Factory\AffiliationFactory;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AffiliationFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AffiliationFactoryInterface::class);

        $this->assertInstanceOf(AffiliationFactory::class, $factory);
    }

    /**
     * 正常系: PENDINGのAffiliationを作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $agencyAccountIdentifier;
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $factory = $this->app->make(AffiliationFactoryInterface::class);
        $affiliation = $factory->create(
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            $terms,
        );

        $this->assertTrue(UuidValidator::isValid((string) $affiliation->affiliationIdentifier()));
        $this->assertSame($agencyAccountIdentifier, $affiliation->agencyAccountIdentifier());
        $this->assertSame($talentAccountIdentifier, $affiliation->talentAccountIdentifier());
        $this->assertSame($requestedBy, $affiliation->requestedBy());
        $this->assertSame(AffiliationStatus::PENDING, $affiliation->status());
        $this->assertSame($terms, $affiliation->terms());
        $this->assertNotNull($affiliation->requestedAt());
        $this->assertNull($affiliation->activatedAt());
        $this->assertNull($affiliation->terminatedAt());
    }

    /**
     * 正常系: termsがnullのAffiliationを作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithoutTerms(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $talentAccountIdentifier;

        $factory = $this->app->make(AffiliationFactoryInterface::class);
        $affiliation = $factory->create(
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            null,
        );

        $this->assertTrue(UuidValidator::isValid((string) $affiliation->affiliationIdentifier()));
        $this->assertSame($agencyAccountIdentifier, $affiliation->agencyAccountIdentifier());
        $this->assertSame($talentAccountIdentifier, $affiliation->talentAccountIdentifier());
        $this->assertSame($requestedBy, $affiliation->requestedBy());
        $this->assertSame(AffiliationStatus::PENDING, $affiliation->status());
        $this->assertNull($affiliation->terms());
        $this->assertNotNull($affiliation->requestedAt());
        $this->assertNull($affiliation->activatedAt());
        $this->assertNull($affiliation->terminatedAt());
    }
}
