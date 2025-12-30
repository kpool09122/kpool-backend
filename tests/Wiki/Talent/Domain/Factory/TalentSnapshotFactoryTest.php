<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Factory\TalentSnapshotFactory;
use Source\Wiki\Talent\Domain\Factory\TalentSnapshotFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentSnapshotFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(TalentSnapshotFactoryInterface::class);
        $this->assertInstanceOf(TalentSnapshotFactory::class, $factory);
    }

    /**
     * 正常系: TalentSnapshot Entityが正しく作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $career = new Career('TWICE member since 2015.');
        $imageLink = new ImagePath('/resources/public/images/chaeyoung.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=1');
        $link2 = new ExternalContentLink('https://example.youtube.com/watch?v=2');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2]);
        $version = new Version(3);

        $talent = new Talent(
            $talentIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $version,
        );

        $factory = $this->app->make(TalentSnapshotFactoryInterface::class);
        $snapshot = $factory->create($talent);

        $this->assertTrue(UlidValidator::isValid((string)$snapshot->snapshotIdentifier()));
        $this->assertSame((string)$talentIdentifier, (string)$snapshot->talentIdentifier());
        $this->assertSame((string)$translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($language->value, $snapshot->language()->value);
        $this->assertSame((string)$name, (string)$snapshot->name());
        $this->assertSame((string)$realName, (string)$snapshot->realName());
        $this->assertSame((string)$agencyIdentifier, (string)$snapshot->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $snapshot->groupIdentifiers());
        $this->assertSame($birthday->value(), $snapshot->birthday()->value());
        $this->assertSame((string)$career, (string)$snapshot->career());
        $this->assertSame((string)$imageLink, (string)$snapshot->imageLink());
        $this->assertSame($relevantVideoLinks->toStringArray(), $snapshot->relevantVideoLinks()->toStringArray());
        $this->assertSame($version->value(), $snapshot->version()->value());
        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->createdAt());
    }

    /**
     * 正常系: agencyIdentifierがnullのTalentからSnapshotが作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithNullAgencyIdentifier(): void
    {
        $talent = new Talent(
            new TalentIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new TalentName('채영'),
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            new Version(1),
        );

        $factory = $this->app->make(TalentSnapshotFactoryInterface::class);
        $snapshot = $factory->create($talent);

        $this->assertNull($snapshot->agencyIdentifier());
        $this->assertNull($snapshot->birthday());
        $this->assertNull($snapshot->imageLink());
    }
}
