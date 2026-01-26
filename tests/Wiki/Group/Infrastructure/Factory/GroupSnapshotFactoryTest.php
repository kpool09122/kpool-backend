<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Factory\GroupSnapshotFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Infrastructure\Factory\GroupSnapshotFactory;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupSnapshotFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(GroupSnapshotFactoryInterface::class);
        $this->assertInstanceOf(GroupSnapshotFactory::class, $factory);
    }

    /**
     * 正常系: GroupSnapshot Entityが正しく作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('twice');
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('TWICE is a South Korean girl group.');
        $version = new Version(3);
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2024-01-02 00:00:00');
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translatedAt = new DateTimeImmutable('2024-01-03 00:00:00');
        $approvedAt = new DateTimeImmutable('2024-01-04 00:00:00');

        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
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

        $factory = $this->app->make(GroupSnapshotFactoryInterface::class);
        $snapshot = $factory->create($group);

        $this->assertTrue(UuidValidator::isValid((string)$snapshot->snapshotIdentifier()));
        $this->assertSame((string)$groupIdentifier, (string)$snapshot->groupIdentifier());
        $this->assertSame((string)$translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($language->value, $snapshot->language()->value);
        $this->assertSame((string)$name, (string)$snapshot->name());
        $this->assertSame($normalizedName, $snapshot->normalizedName());
        $this->assertSame((string)$agencyIdentifier, (string)$snapshot->agencyIdentifier());
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
     * 正常系: agencyIdentifierがnullのGroupからSnapshotが作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithNullAgencyIdentifier(): void
    {
        $group = new Group(
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('twice'),
            Language::KOREAN,
            new GroupName('TWICE'),
            'twice',
            null,
            new Description('TWICE is a South Korean girl group.'),
            new Version(1),
        );

        $factory = $this->app->make(GroupSnapshotFactoryInterface::class);
        $snapshot = $factory->create($group);

        $this->assertNull($snapshot->agencyIdentifier());
    }
}
