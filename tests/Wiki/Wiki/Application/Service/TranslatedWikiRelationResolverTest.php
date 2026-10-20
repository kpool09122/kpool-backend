<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\Service;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Wiki\Application\Service\TranslatedWikiRelationResolver;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslatedWikiRelationResolverTest extends TestCase
{
    /** @return array<string, array{class-string<GroupBasic|SongBasic|TalentBasic>}> */
    public static function basicTypes(): array
    {
        return ['group' => [GroupBasic::class], 'song' => [SongBasic::class], 'talent' => [TalentBasic::class]];
    }

    /** @param class-string<GroupBasic|SongBasic|TalentBasic> $basicClass */
    #[DataProvider('basicTypes')]
    public function testResolveReplacesOnlyRelationsAvailableInTargetLanguage(string $basicClass): void
    {
        $sourceId = new WikiIdentifier(StrTestHelper::generateUuid());
        $targetId = new WikiIdentifier(StrTestHelper::generateUuid());
        $untranslatedId = new WikiIdentifier(StrTestHelper::generateUuid());
        $missingId = new WikiIdentifier(StrTestHelper::generateUuid());
        $sourceSet = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $untranslatedSet = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $source = Mockery::mock(Wiki::class);
        $source->shouldReceive('language')->andReturn(Language::KOREAN);
        $source->shouldReceive('translationSetIdentifier')->andReturn($sourceSet);
        $target = Mockery::mock(Wiki::class);
        $target->shouldReceive('language')->andReturn(Language::JAPANESE);
        $target->shouldReceive('wikiIdentifier')->andReturn($targetId);
        $untranslated = Mockery::mock(Wiki::class);
        $untranslated->shouldReceive('language')->andReturn(Language::KOREAN);
        $untranslated->shouldReceive('translationSetIdentifier')->andReturn($untranslatedSet);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $sourceId)->andReturn($source);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $untranslatedId)->andReturn($untranslated);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $missingId)->andReturn(null);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')->with($sourceSet, [Language::JAPANESE])->andReturn([$target]);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifierAndLanguages')->with($untranslatedSet, [Language::JAPANESE])->andReturn([]);
        $original = $basicClass::fromArray([
            'name' => '翻訳済みの名前',
            'agency_identifier' => (string) $untranslatedId,
            'group_identifiers' => [(string) $untranslatedId, (string) $sourceId, (string) $missingId],
            'talent_identifiers' => [(string) $sourceId, (string) $untranslatedId],
        ]);
        $before = $original->toArray();
        $result = (new TranslatedWikiRelationResolver($wikiRepository))->resolve($original, Language::JAPANESE);
        $expected = $before;
        $expected['agency_identifier'] = null;
        foreach (['group_identifiers', 'talent_identifiers'] as $key) {
            if (array_key_exists($key, $expected)) {
                $expected[$key] = [(string) $targetId];
            }
        }
        $this->assertSame($expected, $result->toArray());
        $this->assertSame($before, $original->toArray());
    }

    public function testResolveKeepsExistingTargetAndRemovesMissingRelations(): void
    {
        $targetId = new WikiIdentifier(StrTestHelper::generateUuid());
        $missingId = new WikiIdentifier(StrTestHelper::generateUuid());
        $target = Mockery::mock(Wiki::class);
        $target->shouldReceive('language')->andReturn(Language::JAPANESE);
        $target->shouldReceive('wikiIdentifier')->andReturn($targetId);
        /** @var WikiRepositoryInterface&MockInterface $wikiRepository */
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $targetId)->andReturn($target);
        $wikiRepository->shouldReceive('findById')->withArgs(fn (WikiIdentifier $id) => (string) $id === (string) $missingId)->andReturn(null);
        $basic = SongBasic::fromArray([
            'name' => 'Song',
            'agency_identifier' => (string) $targetId,
            'group_identifiers' => [(string) $missingId],
            'talent_identifiers' => [(string) $missingId],
        ]);
        $result = (new TranslatedWikiRelationResolver($wikiRepository))->resolve($basic, Language::JAPANESE)->toArray();
        $this->assertSame((string) $targetId, $result['agency_identifier']);
        $this->assertSame([], $result['group_identifiers']);
        $this->assertSame([], $result['talent_identifiers']);
    }
}
