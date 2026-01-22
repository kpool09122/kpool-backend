<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Principal\Domain\Factory\DemotionWarningFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\WarningCount;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Infrastructure\Factory\DemotionWarningFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DemotionWarningFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(DemotionWarningFactoryInterface::class);
        $this->assertInstanceOf(DemotionWarningFactory::class, $factory);
    }

    /**
     * 正常系: DemotionWarningが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $lastWarningMonth = YearMonth::fromDateTime(new DateTimeImmutable());

        $factory = $this->app->make(DemotionWarningFactoryInterface::class);
        $demotionWarning = $factory->create(
            $principalIdentifier,
            $lastWarningMonth,
        );

        $this->assertTrue(UuidValidator::isValid((string) $demotionWarning->id()));
        $this->assertSame($principalIdentifier, $demotionWarning->principalIdentifier());
        $this->assertEquals(new WarningCount(1), $demotionWarning->warningCount());
        $this->assertSame($lastWarningMonth, $demotionWarning->lastWarningMonth());
        $this->assertInstanceOf(DateTimeImmutable::class, $demotionWarning->createdAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $demotionWarning->updatedAt());
    }
}
