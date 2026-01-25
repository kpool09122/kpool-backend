<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Grading\Domain\Facotory\PromotionHistoryFactoryInterface;
use Source\Wiki\Grading\Infrastructure\Factory\PromotionHistoryFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PromotionHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(PromotionHistoryFactoryInterface::class);
        $this->assertInstanceOf(PromotionHistoryFactory::class, $factory);
    }

    /**
     * 正常系: reasonありでPromotionHistoryが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithReason(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromRole = 'COLLABORATOR';
        $toRole = 'SENIOR_COLLABORATOR';
        $reason = 'Promoted for being in top 10% with 125 points';

        $factory = $this->app->make(PromotionHistoryFactoryInterface::class);
        $history = $factory->create(
            $principalIdentifier,
            $fromRole,
            $toRole,
            $reason,
        );

        $this->assertTrue(UuidValidator::isValid((string) $history->id()));
        $this->assertSame($principalIdentifier, $history->principalIdentifier());
        $this->assertSame($fromRole, $history->fromRole());
        $this->assertSame($toRole, $history->toRole());
        $this->assertSame($reason, $history->reason());
        $this->assertNotNull($history->processedAt());
    }

    /**
     * 正常系: reasonなしでPromotionHistoryが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithoutReason(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromRole = 'SENIOR_COLLABORATOR';
        $toRole = 'COLLABORATOR';
        $reason = null;

        $factory = $this->app->make(PromotionHistoryFactoryInterface::class);
        $history = $factory->create(
            $principalIdentifier,
            $fromRole,
            $toRole,
            $reason,
        );

        $this->assertTrue(UuidValidator::isValid((string) $history->id()));
        $this->assertSame($principalIdentifier, $history->principalIdentifier());
        $this->assertSame($fromRole, $history->fromRole());
        $this->assertSame($toRole, $history->toRole());
        $this->assertNull($history->reason());
        $this->assertNotNull($history->processedAt());
    }
}
