<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Factory\AgencySnapshotFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Agency\Infrastructure\Factory\AgencySnapshotFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencySnapshotFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AgencySnapshotFactoryInterface::class);
        $this->assertInstanceOf(AgencySnapshotFactory::class, $factory);
    }

    /**
     * 正常系: AgencySnapshot Entityが正しく作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('jyp-entertainment');
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('JYP Entertainment is a South Korean entertainment company.');
        $version = new Version(3);
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2024-01-02 00:00:00');
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translatedAt = new DateTimeImmutable('2024-01-03 00:00:00');
        $approvedAt = new DateTimeImmutable('2024-01-04 00:00:00');

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $version,
            $mergerIdentifier,
            $mergedAt,
            $editorIdentifier,
            $approverIdentifier,
            false,
            null,
            $sourceEditorIdentifier,
            $translatedAt,
            $approvedAt,
        );

        $factory = $this->app->make(AgencySnapshotFactoryInterface::class);
        $snapshot = $factory->create($agency);

        $this->assertTrue(UuidValidator::isValid((string)$snapshot->snapshotIdentifier()));
        $this->assertSame((string)$agencyIdentifier, (string)$snapshot->agencyIdentifier());
        $this->assertSame((string)$translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($language->value, $snapshot->language()->value);
        $this->assertSame((string)$name, (string)$snapshot->name());
        $this->assertSame($normalizedName, $snapshot->normalizedName());
        $this->assertSame((string)$CEO, (string)$snapshot->CEO());
        $this->assertSame($normalizedCEO, $snapshot->normalizedCEO());
        $this->assertSame($foundedIn->value(), $snapshot->foundedIn()->value());
        $this->assertSame((string)$description, (string)$snapshot->description());
        $this->assertSame($version->value(), $snapshot->version()->value());
        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->createdAt());
        $this->assertSame((string)$editorIdentifier, (string)$snapshot->editorIdentifier());
        $this->assertSame((string)$approverIdentifier, (string)$snapshot->approverIdentifier());
        $this->assertSame((string)$mergerIdentifier, (string)$snapshot->mergerIdentifier());
        $this->assertSame($mergedAt->format('Y-m-d H:i:s'), $snapshot->mergedAt()->format('Y-m-d H:i:s'));
        $this->assertSame((string)$sourceEditorIdentifier, (string)$snapshot->sourceEditorIdentifier());
        $this->assertSame($translatedAt->format('Y-m-d H:i:s'), $snapshot->translatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame($approvedAt->format('Y-m-d H:i:s'), $snapshot->approvedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系: foundedInがnullのAgencyからSnapshotが作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithNullFoundedIn(): void
    {
        $agency = new Agency(
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('sm-entertainment'),
            Language::KOREAN,
            new AgencyName('SM엔터테인먼트'),
            'smㅇㅌㅌㅇㅁㅌ',
            new CEO('Lee Sung-su'),
            'lee sung-su',
            null,
            new Description('SM Entertainment is a South Korean entertainment company.'),
            new Version(1),
        );

        $factory = $this->app->make(AgencySnapshotFactoryInterface::class);
        $snapshot = $factory->create($agency);

        $this->assertNull($snapshot->foundedIn());
    }
}
