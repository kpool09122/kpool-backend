<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Factory\AgencyFactory;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Tests\Helper\StrTestHelper;
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
        $translation = Language::KOREAN;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $agencyFactory = $this->app->make(AgencyFactoryInterface::class);
        $agency = $agencyFactory->create($translationSetIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$agency->agencyIdentifier()));
        $this->assertSame((string)$translationSetIdentifier, (string)$agency->translationSetIdentifier());
        $this->assertSame($translation->value, $agency->language()->value);
        $this->assertSame((string)$name, (string)$agency->name());
        $this->assertSame('jypㅇㅌㅌㅇㅁㅌ', $agency->normalizedName());
        $this->assertSame('', (string)$agency->CEO());
        $this->assertSame('', $agency->normalizedCEO());
        $this->assertNull($agency->foundedIn());
        $this->assertSame('', (string)$agency->description());
    }
}
