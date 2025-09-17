<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Agency\Domain\Factory\AgencyFactory;
use Businesses\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;

class AgencyFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $agencyFactory = $this->app->make(AgencyFactoryInterface::class);
        $this->assertInstanceOf(AgencyFactory::class, $agencyFactory);
    }

    /**
     * 正常系: Agency Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $name = new AgencyName('JYP엔터테인먼트');
        $translation = Translation::KOREAN;
        $agencyFactory = $this->app->make(AgencyFactoryInterface::class);
        $agency = $agencyFactory->create($translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$agency->agencyIdentifier()));
        $this->assertSame($translation->value, $agency->translation()->value);
        $this->assertSame((string)$name, (string)$agency->name());
        $this->assertSame('', (string)$agency->CEO());
        $this->assertNull($agency->foundedIn());
        $this->assertSame('', (string)$agency->description());
    }
}
