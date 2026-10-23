<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\Service;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\VideoLink\Application\Service\VideoSearchKeywordBuilder;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class VideoSearchKeywordBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGroupSearchIncludesNameAndKPop(): void
    {
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldNotReceive('findById');
        $wiki = $this->groupWiki('TWICE');

        self::assertSame('TWICE k-pop', (new VideoSearchKeywordBuilder($wikiRepository))->build($wiki));
    }

    public function testTalentSearchIncludesAllLinkedGroups(): void
    {
        $group = $this->groupWiki('TWICE');
        $otherGroup = $this->groupWiki('MISAMO');
        $wiki = $this->wiki(TalentBasic::fromArray([
            'name' => 'SANA',
            'agency_identifier' => null,
            'group_identifiers' => [(string) $group->wikiIdentifier(), (string) $otherGroup->wikiIdentifier()],
        ]), ResourceType::TALENT);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->once()->withArgs(
            static fn (WikiIdentifier $id): bool => (string) $id === (string) $group->wikiIdentifier(),
        )->andReturn($group);
        $wikiRepository->shouldReceive('findById')->once()->withArgs(
            static fn (WikiIdentifier $id): bool => (string) $id === (string) $otherGroup->wikiIdentifier(),
        )->andReturn($otherGroup);

        self::assertSame('SANA TWICE MISAMO k-pop', (new VideoSearchKeywordBuilder($wikiRepository))->build($wiki));
    }

    public function testSongSearchIncludesLinkedGroupsAndTalents(): void
    {
        $group = $this->groupWiki('TWICE');
        $talent = $this->wiki(TalentBasic::fromArray([
            'name' => 'NAYEON', 'agency_identifier' => null, 'group_identifiers' => [],
        ]), ResourceType::TALENT);
        $wiki = $this->wiki(SongBasic::fromArray([
            'name' => 'POP!',
            'group_identifiers' => [(string) $group->wikiIdentifier()],
            'talent_identifiers' => [(string) $talent->wikiIdentifier()],
        ]), ResourceType::SONG);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->once()->withArgs(
            static fn (WikiIdentifier $id): bool => (string) $id === (string) $group->wikiIdentifier(),
        )->andReturn($group);
        $wikiRepository->shouldReceive('findById')->once()->withArgs(
            static fn (WikiIdentifier $id): bool => (string) $id === (string) $talent->wikiIdentifier(),
        )->andReturn($talent);

        self::assertSame('POP! TWICE NAYEON k-pop', (new VideoSearchKeywordBuilder($wikiRepository))->build($wiki));
    }

    public function testMissingRelatedWikiIsSkippedAndDuplicateNamesAreRemoved(): void
    {
        $group = $this->groupWiki('TWICE');
        $missingIdentifier = StrTestHelper::generateUuid();
        $wiki = $this->wiki(SongBasic::fromArray([
            'name' => 'TWICE',
            'group_identifiers' => [(string) $group->wikiIdentifier()],
            'talent_identifiers' => [$missingIdentifier],
        ]), ResourceType::SONG);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->once()->withArgs(
            static fn (WikiIdentifier $id): bool => (string) $id === (string) $group->wikiIdentifier(),
        )->andReturn($group);
        $wikiRepository->shouldReceive('findById')->once()->withArgs(
            static fn (WikiIdentifier $id): bool => (string) $id === $missingIdentifier,
        )->andReturn(null);

        self::assertSame('TWICE k-pop', (new VideoSearchKeywordBuilder($wikiRepository))->build($wiki));
    }

    private function groupWiki(string $name): Wiki
    {
        return $this->wiki(GroupBasic::fromArray(['name' => $name, 'agency_identifier' => null]), ResourceType::GROUP);
    }

    private function wiki(BasicInterface $basic, ResourceType $resourceType): Wiki
    {
        return new Wiki(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-test'),
            Language::KOREAN,
            $resourceType,
            $basic,
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }
}
