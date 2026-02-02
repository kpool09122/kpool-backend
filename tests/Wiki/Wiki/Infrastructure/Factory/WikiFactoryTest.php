<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Factory\WikiFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Infrastructure\Factory\WikiFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $wikiFactory = $this->app->make(WikiFactoryInterface::class);
        $this->assertInstanceOf(WikiFactory::class, $wikiFactory);
    }

    /**
     * 正常系: Wiki Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('twice');
        $language = Language::KOREAN;
        $resourceType = ResourceType::GROUP;
        $basic = new GroupBasic(
            name: new Name('TWICE'),
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('ONCE'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $wikiFactory = $this->app->make(WikiFactoryInterface::class);
        $wiki = $wikiFactory->create($translationSetIdentifier, $slug, $language, $resourceType, $basic);
        $this->assertTrue(UuidValidator::isValid((string)$wiki->wikiIdentifier()));
        $this->assertSame((string)$translationSetIdentifier, (string)$wiki->translationSetIdentifier());
        $this->assertSame((string)$slug, (string)$wiki->slug());
        $this->assertSame($language->value, $wiki->language()->value);
        $this->assertSame($resourceType, $wiki->resourceType());
        $this->assertSame($basic, $wiki->basic());
        $this->assertTrue($wiki->sections()->isEmpty());
        $this->assertNull($wiki->themeColor());
        $this->assertSame(1, $wiki->version()->value());
        $this->assertNull($wiki->ownerAccountIdentifier());
        $this->assertNull($wiki->editorIdentifier());
        $this->assertNull($wiki->approverIdentifier());
        $this->assertNull($wiki->mergerIdentifier());
        $this->assertNull($wiki->sourceEditorIdentifier());
        $this->assertNull($wiki->mergedAt());
        $this->assertNull($wiki->translatedAt());
        $this->assertNull($wiki->approvedAt());
    }
}
