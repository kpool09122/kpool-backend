<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiOutput;
use Source\Wiki\Wiki\Application\UseCase\Command\EditWiki\EditWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\EditWiki\EditWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\EditWiki\EditWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiBasicNormalizationTest extends TestCase
{
    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('normalizationCases')]
    public function testCreateSavesRecomputedValues(Language $language, BasicInterface $basic, Slug $slug, array $expected): void
    {
        $principalIdentifier = $this->authorize();
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findBySlugAndResourceType')->once()->andReturn([]);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->expectSavedBasic($basic, $expected);

        $input = new CreateWikiInput(
            null,
            $language,
            ResourceType::fromSlug($slug),
            $basic,
            new SectionContentCollection(),
            null,
            $slug,
            $principalIdentifier,
        );
        $this->app()->make(CreateWikiInterface::class)->process($input, new CreateWikiOutput());
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('normalizationCases')]
    public function testEditUsesStoredLanguageAndReplacesStaleValues(Language $language, BasicInterface $basic, Slug $slug, array $expected): void
    {
        $principalIdentifier = $this->authorize();
        $oldBasic = $basic::fromArray(array_replace($basic->toArray(), ['name' => 'Old Name']));
        $draftWiki = $this->app()->make(DraftWikiFactoryInterface::class)->create($principalIdentifier, $language, $oldBasic, $slug);
        $this->expectSavedBasic($basic, $expected, $draftWiki);

        $input = new EditWikiInput(
            $draftWiki->wikiIdentifier(),
            $basic,
            new SectionContentCollection(),
            null,
            $principalIdentifier,
            ResourceType::fromSlug($slug),
        );
        $this->app()->make(EditWikiInterface::class)->process($input, new EditWikiOutput());
    }

    private function authorize(): PrincipalIdentifier
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn($principal);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        return $principalIdentifier;
    }

    /**
     * @param array<string, string> $expected
     */
    private function expectSavedBasic(BasicInterface $basic, array $expected, ?DraftWiki $existing = null): void
    {
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        if ($existing !== null) {
            $draftWikiRepository->shouldReceive('findById')->once()->with($existing->wikiIdentifier())->andReturn($existing);
        }
        $draftWikiRepository->shouldReceive('save')->once()->with(Mockery::on(function (DraftWiki $draftWiki) use ($basic, $expected): bool {
            self::assertSame(array_replace($basic->toArray(), $expected), $draftWiki->basic()->toArray());

            return true;
        }));
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
    }

    /**
     * @return iterable<string, array{Language, BasicInterface, Slug, array<string, string>}>
     */
    public static function normalizationCases(): iterable
    {
        $types = [
            'ag' => [AgencyBasic::class, ['ceo' => 'normalized_ceo']],
            'gr' => [GroupBasic::class, []],
            'tl' => [TalentBasic::class, ['real_name' => 'normalized_real_name']],
            'sg' => [SongBasic::class, ['lyricist' => 'normalized_lyricist', 'composer' => 'normalized_composer', 'arranger' => 'normalized_arranger']],
        ];
        $languages = [
            [Language::JAPANESE, 'カタカナABC', 'かたかなabc'],
            [Language::KOREAN, '박진영ABC', 'ㅂㅈㅇabc'],
            [Language::ENGLISH, 'Real Name', 'real name'],
        ];
        foreach ($types as $prefix => [$class, $fields]) {
            foreach ($languages as [$language, $source, $normalized]) {
                foreach ([false, true] as $emptyOptionalFields) {
                    $data = ['name' => $source, 'normalized_name' => 'stale-client-value', 'agency_identifier' => null, 'group_identifiers' => []];
                    $expected = ['normalized_name' => $normalized];
                    foreach ($fields as $sourceKey => $normalizedKey) {
                        $data[$sourceKey] = $emptyOptionalFields ? '' : $source;
                        $data[$normalizedKey] = 'stale-client-value';
                        $expected[$normalizedKey] = $emptyOptionalFields ? '' : $normalized;
                    }
                    yield $prefix . '-' . $language->value . '-' . (int) $emptyOptionalFields => [$language, $class::fromArray($data), new Slug($prefix . '-test'), $expected];
                }
            }
        }
    }
}
