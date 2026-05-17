<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\Support;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Infrastructure\Repository\SectionContentMapper;

final class WikiCommandPayloadMapper
{
    /**
     * @param array<string, mixed> $basic
     */
    public static function basic(ResourceType $resourceType, array $basic): BasicInterface
    {
        return match ($resourceType) {
            ResourceType::AGENCY => AgencyBasic::fromArray(self::agencyBasic($basic)),
            ResourceType::GROUP => GroupBasic::fromArray(self::groupBasic($basic)),
            ResourceType::TALENT => TalentBasic::fromArray(self::talentBasic($basic)),
            ResourceType::SONG => SongBasic::fromArray(self::songBasic($basic)),
            ResourceType::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not have a Basic.'),
        };
    }

    /**
     * @param array<int, mixed> $sections
     */
    public static function sections(array $sections): SectionContentCollection
    {
        return SectionContentMapper::collectionFromArray(
            self::sectionContents($sections, 'sections'),
        );
    }

    /**
     * @param array<int, mixed> $contents
     * @return array<int, array<string, mixed>>
     */
    private static function sectionContents(array $contents, string $path): array
    {
        $mapped = [];

        foreach ($contents as $index => $content) {
            if (! is_array($content)) {
                throw new InvalidArgumentException(sprintf('Section content must be an object at %s.%s.', $path, $index));
            }

            $mapped[] = self::sectionContent($content, sprintf('%s.%s', $path, $index));
        }

        return $mapped;
    }

    /**
     * @param array<string, mixed> $basic
     * @return array<string, mixed>
     */
    private static function groupBasic(array $basic): array
    {
        return [
            'name' => $basic['name'],
            'normalized_name' => $basic['normalizedName'] ?? '',
            'agency_identifier' => $basic['agencyIdentifier'] ?? null,
            'group_type' => $basic['groupType'] ?? null,
            'status' => $basic['status'] ?? null,
            'generation' => $basic['generation'] ?? null,
            'debut_date' => $basic['debutDate'] ?? null,
            'disband_date' => $basic['disbandDate'] ?? null,
            'fandom_name' => $basic['fandomName'] ?? '',
            'official_colors' => $basic['officialColors'] ?? [],
            'emoji' => $basic['emoji'] ?? '',
            'representative_symbol' => $basic['representativeSymbol'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $basic
     * @return array<string, mixed>
     */
    private static function talentBasic(array $basic): array
    {
        return [
            'name' => $basic['name'],
            'normalized_name' => $basic['normalizedName'] ?? '',
            'real_name' => $basic['realName'] ?? '',
            'normalized_real_name' => $basic['normalizedRealName'] ?? '',
            'birthday' => $basic['birthday'] ?? null,
            'agency_identifier' => $basic['agencyIdentifier'] ?? null,
            'group_identifiers' => $basic['groupIdentifiers'] ?? [],
            'emoji' => $basic['emoji'] ?? '',
            'representative_symbol' => $basic['representativeSymbol'] ?? '',
            'position' => $basic['position'] ?? '',
            'mbti' => $basic['mbti'] ?? null,
            'zodiac_sign' => $basic['zodiacSign'] ?? null,
            'english_level' => $basic['englishLevel'] ?? null,
            'height' => $basic['height'] ?? null,
            'blood_type' => $basic['bloodType'] ?? null,
            'fandom_name' => $basic['fandomName'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $basic
     * @return array<string, mixed>
     */
    private static function agencyBasic(array $basic): array
    {
        return [
            'name' => $basic['name'],
            'normalized_name' => $basic['normalizedName'] ?? '',
            'ceo' => $basic['ceo'] ?? '',
            'normalized_ceo' => $basic['normalizedCeo'] ?? '',
            'founded_in' => $basic['foundedIn'] ?? null,
            'parent_agency_identifier' => $basic['parentAgencyIdentifier'] ?? null,
            'status' => $basic['status'] ?? null,
            'official_website' => $basic['officialWebsite'] ?? null,
            'social_links' => $basic['socialLinks'] ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $basic
     * @return array<string, mixed>
     */
    private static function songBasic(array $basic): array
    {
        return [
            'name' => $basic['name'],
            'normalized_name' => $basic['normalizedName'] ?? '',
            'song_type' => $basic['songType'] ?? null,
            'genres' => $basic['genres'] ?? [],
            'agency_identifier' => $basic['agencyIdentifier'] ?? null,
            'group_identifiers' => $basic['groupIdentifiers'] ?? [],
            'talent_identifiers' => $basic['talentIdentifiers'] ?? [],
            'release_date' => $basic['releaseDate'] ?? null,
            'album_name' => $basic['albumName'] ?? null,
            'lyricist' => $basic['lyricist'] ?? '',
            'normalized_lyricist' => $basic['normalizedLyricist'] ?? '',
            'composer' => $basic['composer'] ?? '',
            'normalized_composer' => $basic['normalizedComposer'] ?? '',
            'arranger' => $basic['arranger'] ?? '',
            'normalized_arranger' => $basic['normalizedArranger'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    private static function sectionContent(array $content, string $path): array
    {
        $type = $content['type'] ?? '';

        if ($type === 'section') {
            return [
                'type' => 'section',
                'title' => $content['title'] ?? '',
                'display_order' => $content['displayOrder'] ?? 0,
                'contents' => self::sectionContents($content['contents'] ?? [], $path . '.contents'),
            ];
        }

        if ($type === '') {
            throw new InvalidArgumentException(sprintf(
                'Section content type is required at %s. keys: %s.',
                $path,
                implode(', ', array_keys($content)),
            ));
        }

        if (BlockType::tryFrom($type) === null) {
            throw new InvalidArgumentException('Unknown block type: ' . $type);
        }

        return [
            'block_type' => $type,
            'display_order' => $content['displayOrder'] ?? 0,
            'content' => $content['content'] ?? '',
            'image_identifier' => $content['imageIdentifier'] ?? null,
            'image_identifiers' => $content['imageIdentifiers'] ?? [],
            'caption' => $content['caption'] ?? null,
            'alt' => $content['alt'] ?? null,
            'provider' => $content['provider'] ?? null,
            'embed_id' => $content['embedId'] ?? '',
            'source' => $content['source'] ?? null,
            'list_type' => $content['listType'] ?? 'bullet',
            'items' => $content['items'] ?? [],
            'header_cells' => $content['headerCells'] ?? null,
            'row_cells' => $content['rowCells'] ?? [],
            'table_width' => $content['tableWidth'] ?? null,
            'wiki_identifiers' => $content['wikiIdentifiers'] ?? [],
            'title' => $content['title'] ?? null,
        ];
    }
}
