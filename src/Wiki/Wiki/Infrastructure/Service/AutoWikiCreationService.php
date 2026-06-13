<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyParams;
use Application\Http\Client\GeminiClient\GenerateAgency\GenerateAgencyRequest;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupParams;
use Application\Http\Client\GeminiClient\GenerateGroup\GenerateGroupRequest;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongParams;
use Application\Http\Client\GeminiClient\GenerateSong\GenerateSongRequest;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentParams;
use Application\Http\Client\GeminiClient\GenerateTalent\GenerateTalentRequest;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Application\DTO\SourceReference;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\GeneratedWikiData;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\AutoWikiCreationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyStatus;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\Generation;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupStatus;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongGenre;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Birthday;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\BloodType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\EnglishLevel;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\MBTI;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\ZodiacSign;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Throwable;

readonly class AutoWikiCreationService implements AutoWikiCreationServiceInterface
{
    public function __construct(
        private GeminiClient $geminiClient,
        private LoggerInterface $logger,
        private WikiRepositoryInterface $wikiRepository,
    ) {
    }

    public function generate(
        AutoWikiCreationPayload $payload,
    ): GeneratedWikiData {
        return match ($payload->resourceType()) {
            ResourceType::AGENCY => $this->generateAgency($payload),
            ResourceType::GROUP => $this->generateGroup($payload),
            ResourceType::TALENT => $this->generateTalent($payload),
            ResourceType::SONG => $this->generateSong($payload),
            default => throw new InvalidArgumentException("Unsupported resource type: {$payload->resourceType()->value}"),
        };
    }

    private function generateAgency(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $request = new GenerateAgencyRequest(
            agencyName: (string) $payload->name(),
            language: $payload->language()->value,
        );

        try {
            $response = $this->geminiClient->generateAgency($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateAgencyParams::empty();
        }

        $basic = AgencyBasic::fromArray([
            'type' => 'agency',
            'name' => (string) $payload->name(),
            'ceo' => $params->ceoName() ?? '',
            'founded_in' => $this->validDate($params->foundedIn())
                ?? ($params->foundedYear() !== null ? $params->foundedYear() . '-01-01' : null),
            'status' => $this->allowedValue($params->status(), AgencyStatus::cases()),
            'official_website' => $this->validHttpsUrl($params->officialWebsite()),
            'social_links' => $this->validHttpsUrls($params->socialLinks()),
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildAgencySections($params, $payload->language()->value),
            sources: $params->sources(),
        );
    }

    private function generateGroup(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $agencyName = $this->resolveAgencyName($payload->agencyIdentifier());

        $request = new GenerateGroupRequest(
            groupName: (string) $payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
        );

        try {
            $response = $this->geminiClient->generateGroup($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateGroupParams::empty();
        }

        $basic = GroupBasic::fromArray([
            'type' => 'group',
            'name' => (string) $payload->name(),
            'agency_identifier' => $payload->agencyIdentifier() !== null ? (string) $payload->agencyIdentifier() : null,
            'group_type' => $this->allowedValue($params->groupType(), GroupType::cases()),
            'status' => $this->allowedValue($params->status(), GroupStatus::cases()),
            'generation' => $this->allowedValue($params->generation(), Generation::cases()),
            'debut_date' => $this->validDate($params->debutDate()),
            'disband_date' => $this->validDate($params->disbandDate()),
            'fandom_name' => $params->fandomName() ?? '',
            'official_colors' => $this->validHexColors($params->officialColors()),
            'emoji' => $params->emoji() ?? '',
            'representative_symbol' => $params->representativeSymbol() ?? '',
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildGroupSections($params, $payload->language()->value),
            sources: $params->sources(),
        );
    }

    private function generateTalent(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $agencyName = $this->resolveAgencyName($payload->agencyIdentifier());
        $groupNames = $this->resolveGroupNames($payload->groupIdentifiers());

        $request = new GenerateTalentRequest(
            talentName: (string) $payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
            groupNames: $groupNames,
        );

        try {
            $response = $this->geminiClient->generateTalent($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateTalentParams::empty();
        }

        $birthday = null;
        if ($params->birthday() !== null) {
            try {
                $birthday = new Birthday(new DateTimeImmutable($params->birthday()));
            } catch (Throwable) {
                // ignore invalid birthday
            }
        }

        $basic = TalentBasic::fromArray([
            'type' => 'talent',
            'name' => (string) $payload->name(),
            'real_name' => $params->realName() ?? '',
            'birthday' => $birthday,
            'agency_identifier' => $payload->agencyIdentifier() !== null ? (string) $payload->agencyIdentifier() : null,
            'group_identifiers' => array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->groupIdentifiers(),
            ),
            'emoji' => $params->emoji() ?? '',
            'representative_symbol' => $params->representativeSymbol() ?? '',
            'position' => $params->position() ?? '',
            'mbti' => $this->allowedValue($params->mbti(), MBTI::cases()),
            'zodiac_sign' => $this->allowedValue($params->zodiacSign(), ZodiacSign::cases()),
            'english_level' => $this->allowedValue($params->englishLevel(), EnglishLevel::cases()),
            'height' => $params->height() !== null && $params->height() > 0 ? $params->height() : null,
            'blood_type' => $this->allowedValue($params->bloodType(), BloodType::cases()),
            'fandom_name' => $params->fandomName() ?? '',
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildTalentSections($params, $payload->language()->value),
            sources: $params->sources(),
        );
    }

    private function generateSong(AutoWikiCreationPayload $payload): GeneratedWikiData
    {
        $agencyName = $this->resolveAgencyName($payload->agencyIdentifier());

        $groupIdentifiers = $payload->groupIdentifiers();
        $groupName = null;
        if ($groupIdentifiers !== []) {
            $groupNames = $this->resolveGroupNames($groupIdentifiers);
            $groupName = $groupNames[0] ?? null;
        }

        $talentIdentifiers = $payload->talentIdentifiers();
        $talentName = null;
        if ($talentIdentifiers !== []) {
            $talentName = $this->resolveTalentName($talentIdentifiers[0]);
        }

        $request = new GenerateSongRequest(
            songName: (string) $payload->name(),
            language: $payload->language()->value,
            agencyName: $agencyName,
            groupName: $groupName,
            talentName: $talentName,
        );

        try {
            $response = $this->geminiClient->generateSong($request);
            $params = $response->params();
        } catch (Throwable $e) {
            $this->logger->error('Gemini API failed', [
                'message' => $e->getMessage(),
            ]);
            $params = GenerateSongParams::empty();
        }

        $basic = SongBasic::fromArray([
            'type' => 'song',
            'name' => (string) $payload->name(),
            'agency_identifier' => $payload->agencyIdentifier() !== null ? (string) $payload->agencyIdentifier() : null,
            'group_identifiers' => array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->groupIdentifiers(),
            ),
            'talent_identifiers' => array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->talentIdentifiers(),
            ),
            'song_type' => $this->allowedValue($params->songType(), SongType::cases()),
            'genres' => $this->allowedValues($params->genres(), SongGenre::cases()),
            'release_date' => $this->validDate($params->releaseDate()),
            'album_name' => $params->albumName(),
            'lyricist' => $params->lyricist() ?? '',
            'composer' => $params->composer() ?? '',
            'arranger' => $params->arranger() ?? '',
        ]);

        return new GeneratedWikiData(
            alphabetName: $params->alphabetName(),
            basic: $basic,
            sections: $this->buildSongSections($params, $payload->language()->value),
            sources: $params->sources(),
        );
    }

    private function resolveAgencyName(?WikiIdentifier $agencyIdentifier): ?string
    {
        if ($agencyIdentifier === null) {
            return null;
        }

        $wiki = $this->wikiRepository->findById($agencyIdentifier);
        if ($wiki === null) {
            return null;
        }

        return (string) $wiki->basic()->name();
    }

    /**
     * @param WikiIdentifier[] $groupIdentifiers
     * @return string[]
     */
    private function resolveGroupNames(array $groupIdentifiers): array
    {
        $names = [];
        foreach ($groupIdentifiers as $groupIdentifier) {
            $wiki = $this->wikiRepository->findById($groupIdentifier);
            if ($wiki !== null) {
                $names[] = (string) $wiki->basic()->name();
            }
        }

        return $names;
    }

    private function resolveTalentName(WikiIdentifier $talentIdentifier): ?string
    {
        $wiki = $this->wikiRepository->findById($talentIdentifier);
        if ($wiki === null) {
            return null;
        }

        return (string) $wiki->basic()->name();
    }

    private function buildGroupSections(GenerateGroupParams $params, string $language): SectionContentCollection
    {
        $sections = [];
        $order = 0;

        if ($params->overview() !== null && $params->overview() !== '') {
            $sections[] = new Section(
                title: section_title('overview', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new TextBlock(displayOrder: 0, content: $params->overview())]),
            );
        }

        if ($params->history() !== null && $params->history() !== '') {
            $sections[] = new Section(
                title: section_title('history', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new TextBlock(displayOrder: 0, content: $params->history())]),
            );
        }

        if ($params->representativeSongs() !== []) {
            $sections[] = new Section(
                title: section_title('representative_songs', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $params->representativeSongs())]),
            );
        }

        if ($params->awards() !== []) {
            $sections[] = new Section(
                title: section_title('awards', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $params->awards())]),
            );
        }

        if ($params->members() !== []) {
            $sections[] = new Section(
                title: section_title('members', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $params->members())]),
            );
        }

        $sourcesSection = $this->buildSourcesSection($params->sources(), $language, $order);
        if ($sourcesSection !== null) {
            $sections[] = $sourcesSection;
        }

        return new SectionContentCollection($sections);
    }

    private function buildAgencySections(GenerateAgencyParams $params, string $language): SectionContentCollection
    {
        $sections = [];
        $order = 0;

        if ($params->overview() !== null && $params->overview() !== '') {
            $sections[] = new Section(
                title: section_title('overview', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new TextBlock(displayOrder: 0, content: $params->overview())]),
            );
        }

        if ($params->history() !== null && $params->history() !== '') {
            $sections[] = new Section(
                title: section_title('agency_history', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new TextBlock(displayOrder: 0, content: $params->history())]),
            );
        }

        if ($params->artists() !== []) {
            $sections[] = new Section(
                title: section_title('artists', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $params->artists())]),
            );
        }

        $sourcesSection = $this->buildSourcesSection($params->sources(), $language, $order);
        if ($sourcesSection !== null) {
            $sections[] = $sourcesSection;
        }

        return new SectionContentCollection($sections);
    }

    private function buildTalentSections(GenerateTalentParams $params, string $language): SectionContentCollection
    {
        $sections = [];
        $order = 0;

        if ($params->overview() !== null && $params->overview() !== '') {
            $sections[] = new Section(
                title: section_title('overview', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new TextBlock(displayOrder: 0, content: $params->overview())]),
            );
        }

        if ($params->history() !== null && $params->history() !== '') {
            $sections[] = new Section(
                title: section_title('history', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new TextBlock(displayOrder: 0, content: $params->history())]),
            );
        }

        if ($params->appearances() !== []) {
            $sections[] = new Section(
                title: section_title('appearances', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $params->appearances())]),
            );
        }

        if ($params->awards() !== []) {
            $sections[] = new Section(
                title: section_title('awards', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $params->awards())]),
            );
        }

        $sourcesSection = $this->buildSourcesSection($params->sources(), $language, $order);
        if ($sourcesSection !== null) {
            $sections[] = $sourcesSection;
        }

        return new SectionContentCollection($sections);
    }

    private function buildSongSections(GenerateSongParams $params, string $language): SectionContentCollection
    {
        $sections = [];
        $order = 0;

        if ($params->overview() !== null && $params->overview() !== '') {
            $sections[] = new Section(
                title: section_title('overview', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new TextBlock(displayOrder: 0, content: $params->overview())]),
            );
        }

        if ($params->chartPerformance() !== []) {
            $sections[] = new Section(
                title: section_title('chart_performance', $language),
                displayOrder: $order++,
                contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $params->chartPerformance())]),
            );
        }

        $sourcesSection = $this->buildSourcesSection($params->sources(), $language, $order);
        if ($sourcesSection !== null) {
            $sections[] = $sourcesSection;
        }

        return new SectionContentCollection($sections);
    }

    /**
     * @param SourceReference[] $sources
     */
    private function buildSourcesSection(array $sources, string $language, int $displayOrder): ?Section
    {
        if ($sources === []) {
            return null;
        }

        $items = array_map(
            static fn (SourceReference $source) => "{$source->title()} ({$source->uri()})",
            $sources,
        );

        return new Section(
            title: section_title('sources', $language),
            displayOrder: $displayOrder,
            contents: new SectionContentCollection([new ListBlock(displayOrder: 0, listType: ListType::BULLET, items: $items)]),
        );
    }

    /**
     * @param array<\BackedEnum> $cases
     */
    private function allowedValue(?string $value, array $cases): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach ($cases as $case) {
            if ($case->value === $value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param string[] $values
     * @param array<\BackedEnum> $cases
     * @return string[]
     */
    private function allowedValues(array $values, array $cases): array
    {
        return array_values(array_filter(
            $values,
            fn (string $value) => $this->allowedValue($value, $cases) !== null,
        ));
    }

    private function validDate(?string $value): ?string
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $value : null;
    }

    private function validHttpsUrl(?string $value): ?string
    {
        if ($value === null || ! str_starts_with($value, 'https://') || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $value;
    }

    /**
     * @param string[] $values
     * @return string[]
     */
    private function validHttpsUrls(array $values): array
    {
        return array_values(array_filter(
            $values,
            fn (string $value) => $this->validHttpsUrl($value) !== null,
        ));
    }

    /**
     * @param string[] $values
     * @return string[]
     */
    private function validHexColors(array $values): array
    {
        return array_values(array_filter(
            $values,
            static fn (string $value) => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value) === 1,
        ));
    }
}
