<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Infrastructure\Factory\DraftWikiFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftWikiFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $wikiFactory = $this->app->make(DraftWikiFactoryInterface::class);
        $this->assertInstanceOf(DraftWikiFactory::class, $wikiFactory);
    }

    /**
     * 正常系: DraftWiki Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
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
        $slug = new Slug('gr-twice');
        $wikiFactory = $this->app->make(DraftWikiFactoryInterface::class);
        $wiki = $wikiFactory->create($editorIdentifier, $language, $basic, $slug);
        $this->assertTrue(UuidValidator::isValid((string)$wiki->wikiIdentifier()));
        $this->assertNull($wiki->publishedWikiIdentifier());
        $this->assertTrue(UuidValidator::isValid((string)$wiki->translationSetIdentifier()));
        $this->assertSame((string)$slug, (string)$wiki->slug());
        $this->assertSame($language->value, $wiki->language()->value);
        $this->assertSame(ResourceType::GROUP, $wiki->resourceType());
        $this->assertSame($basic, $wiki->basic());
        $this->assertTrue($wiki->sections()->isEmpty());
        $this->assertNull($wiki->themeColor());
        $this->assertSame(ApprovalStatus::Pending, $wiki->status());
        $this->assertSame((string)$editorIdentifier, (string)$wiki->editorIdentifier());
        $this->assertNull($wiki->approverIdentifier());
        $this->assertNull($wiki->mergerIdentifier());
        $this->assertNull($wiki->sourceEditorIdentifier());
        $this->assertNull($wiki->mergedAt());
        $this->assertNull($wiki->translatedAt());
        $this->assertNull($wiki->approvedAt());

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $wiki = $wikiFactory->create($editorIdentifier, $language, $basic, $slug, $translationSetIdentifier);
        $this->assertSame((string)$translationSetIdentifier, (string)$wiki->translationSetIdentifier());
    }

    /**
     * 正常系: editorIdentifierがnullの場合もDraftWiki Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithNullEditorIdentifier(): void
    {
        $language = Language::ENGLISH;
        $basic = new GroupBasic(
            name: new Name('NewJeans'),
            normalizedName: 'newjeans',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('Bunnies'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $slug = new Slug('gr-newjeans');
        $wikiFactory = $this->app->make(DraftWikiFactoryInterface::class);
        $wiki = $wikiFactory->create(null, $language, $basic, $slug);
        $this->assertTrue(UuidValidator::isValid((string)$wiki->wikiIdentifier()));
        $this->assertNull($wiki->publishedWikiIdentifier());
        $this->assertTrue(UuidValidator::isValid((string)$wiki->translationSetIdentifier()));
        $this->assertSame((string)$slug, (string)$wiki->slug());
        $this->assertNull($wiki->editorIdentifier());
        $this->assertSame($language->value, $wiki->language()->value);
        $this->assertSame(ResourceType::GROUP, $wiki->resourceType());
        $this->assertSame($basic, $wiki->basic());
        $this->assertTrue($wiki->sections()->isEmpty());
        $this->assertNull($wiki->themeColor());
        $this->assertSame(ApprovalStatus::Pending, $wiki->status());
        $this->assertNull($wiki->approverIdentifier());
        $this->assertNull($wiki->mergerIdentifier());
        $this->assertNull($wiki->sourceEditorIdentifier());
        $this->assertNull($wiki->mergedAt());
        $this->assertNull($wiki->translatedAt());
        $this->assertNull($wiki->approvedAt());
    }
}
