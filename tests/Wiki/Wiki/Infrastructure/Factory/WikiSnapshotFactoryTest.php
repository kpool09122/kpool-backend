<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Factory\WikiSnapshotFactoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Factory\WikiSnapshotFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiSnapshotFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(WikiSnapshotFactoryInterface::class);
        $this->assertInstanceOf(WikiSnapshotFactory::class, $factory);
    }

    /**
     * 正常系: WikiSnapshot Entityが正しく作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
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
        $sections = new SectionContentCollection([], allowBlocks: false);
        $themeColor = new Color('#FF5733');
        $version = new Version(3);
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2024-01-02 00:00:00');
        $translatedAt = new DateTimeImmutable('2024-01-03 00:00:00');
        $approvedAt = new DateTimeImmutable('2024-01-04 00:00:00');

        $wiki = new Wiki(
            $wikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $version,
            null,
            $editorIdentifier,
            $approverIdentifier,
            $mergerIdentifier,
            $sourceEditorIdentifier,
            $mergedAt,
            $translatedAt,
            $approvedAt,
        );

        $factory = $this->app->make(WikiSnapshotFactoryInterface::class);
        $snapshot = $factory->create($wiki);

        $this->assertTrue(UuidValidator::isValid((string)$snapshot->snapshotIdentifier()));
        $this->assertSame((string)$wikiIdentifier, (string)$snapshot->wikiIdentifier());
        $this->assertSame((string)$translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame((string)$slug, (string)$snapshot->slug());
        $this->assertSame($language->value, $snapshot->language()->value);
        $this->assertSame($resourceType, $snapshot->resourceType());
        $this->assertSame($basic, $snapshot->basic());
        $this->assertTrue($snapshot->sections()->isEmpty());
        $this->assertSame((string)$themeColor, (string)$snapshot->themeColor());
        $this->assertSame($version->value(), $snapshot->version()->value());
        $this->assertSame((string)$editorIdentifier, (string)$snapshot->editorIdentifier());
        $this->assertSame((string)$approverIdentifier, (string)$snapshot->approverIdentifier());
        $this->assertSame((string)$mergerIdentifier, (string)$snapshot->mergerIdentifier());
        $this->assertSame((string)$sourceEditorIdentifier, (string)$snapshot->sourceEditorIdentifier());
        $this->assertSame($mergedAt->format('Y-m-d H:i:s'), $snapshot->mergedAt()->format('Y-m-d H:i:s'));
        $this->assertSame($translatedAt->format('Y-m-d H:i:s'), $snapshot->translatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame($approvedAt->format('Y-m-d H:i:s'), $snapshot->approvedAt()->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->createdAt());
    }

    /**
     * 正常系: nullableな項目がnullのWikiからSnapshotが作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithNullableFields(): void
    {
        $wiki = new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('newjeans'),
            Language::ENGLISH,
            ResourceType::GROUP,
            new GroupBasic(
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
            ),
            new SectionContentCollection([], allowBlocks: false),
            null,
            new Version(1),
        );

        $factory = $this->app->make(WikiSnapshotFactoryInterface::class);
        $snapshot = $factory->create($wiki);

        $this->assertTrue(UuidValidator::isValid((string)$snapshot->snapshotIdentifier()));
        $this->assertNull($snapshot->themeColor());
        $this->assertNull($snapshot->editorIdentifier());
        $this->assertNull($snapshot->approverIdentifier());
        $this->assertNull($snapshot->mergerIdentifier());
        $this->assertNull($snapshot->sourceEditorIdentifier());
        $this->assertNull($snapshot->mergedAt());
        $this->assertNull($snapshot->translatedAt());
        $this->assertNull($snapshot->approvedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->createdAt());
    }
}
