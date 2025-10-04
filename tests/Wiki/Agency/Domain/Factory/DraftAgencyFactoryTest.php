<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactory;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftAgencyFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $agencyFactory = $this->app->make(DraftAgencyFactoryInterface::class);
        $this->assertInstanceOf(DraftAgencyFactory::class, $agencyFactory);
    }

    /**
     * 正常系: Agency Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $name = new AgencyName('JYP엔터테인먼트');
        $translation = Translation::KOREAN;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $agencyFactory = $this->app->make(DraftAgencyFactoryInterface::class);
        $agency = $agencyFactory->create($editorIdentifier, $translation, $name, $translationSetIdentifier);
        $this->assertTrue(UlidValidator::isValid((string)$agency->agencyIdentifier()));
        $this->assertNull($agency->publishedAgencyIdentifier());
        $this->assertSame((string)$translationSetIdentifier, (string)$agency->translationSetIdentifier());
        $this->assertSame((string)$editorIdentifier, (string)$agency->editorIdentifier());
        $this->assertSame($translation->value, $agency->translation()->value);
        $this->assertSame((string)$name, (string)$agency->name());
        $this->assertSame('', (string)$agency->CEO());
        $this->assertNull($agency->foundedIn());
        $this->assertSame('', (string)$agency->description());
        $this->assertSame(ApprovalStatus::Pending, $agency->status());
    }
}
