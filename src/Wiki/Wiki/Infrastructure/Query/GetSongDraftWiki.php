<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\DraftWikiSongBasic as DraftWikiSongBasicModel;
use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiImage as WikiImageModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SongWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\SongWikiTalentSummaryReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiGroupSummaryReadModel;

readonly class GetSongDraftWiki implements GetSongDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetSongDraftWikiInputPort $input): DraftWikiReadModel
    {
        $model = DraftWikiModel::query()
            ->select('draft_wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['songBasic.groups.groupBasic', 'songBasic.talents.talentBasic', 'publishedWiki'])
            ->where('draft_wikis.resource_type', ResourceType::SONG->value)
            ->where('draft_wikis.language', $input->language()->value)
            ->where('draft_wikis.slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $this->songBasic($model->songBasic);

        return new DraftWikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::SONG->value,
            version: $model->publishedWiki->version,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $model->image_identifier,
                'src' => ImageUrl::fromPath($model->getAttribute('hero_image_path')),
                'alt' => $model->getAttribute('hero_image_alt_text'),
            ],
            basic: new SongWikiBasicReadModel(
                name: $basic->name,
                normalizedName: $basic->normalized_name,
                songType: $basic->song_type,
                genres: $basic->genres,
                agencyIdentifier: $basic->agency_identifier,
                releaseDate: $basic->release_date,
                albumName: $basic->album_name,
                lyricist: $basic->lyricist,
                normalizedLyricist: $basic->normalized_lyricist,
                composer: $basic->composer,
                normalizedComposer: $basic->normalized_composer,
                arranger: $basic->arranger,
                normalizedArranger: $basic->normalized_arranger,
                groups: $basic->groups->map(fn (WikiModel $group) => $this->groupSummary($group))->values()->all(),
                talents: $basic->talents->map(fn (WikiModel $talent) => $this->talentSummary($talent))->values()->all(),
            ),
            sections: $this->sectionsWithImages($model->sections),
        );
    }

    private function songBasic(?DraftWikiSongBasicModel $basic): DraftWikiSongBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('SongBasic not found for DraftWiki.');
        }

        return $basic;
    }

    /**
     */
    private function groupSummary(WikiModel $group): TalentWikiGroupSummaryReadModel
    {
        $basic = $group->groupBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for Wiki.');
        }

        return new TalentWikiGroupSummaryReadModel(
            wikiIdentifier: $group->id,
            slug: $group->slug,
            language: $group->language,
            name: $basic->name,
            normalizedName: $basic->normalized_name,
            agencyIdentifier: $basic->agency_identifier,
            groupType: $basic->group_type,
            status: $basic->status,
            generation: $basic->generation,
            debutDate: $basic->debut_date,
            disbandDate: $basic->disband_date,
            fandomName: $basic->fandom_name,
            officialColors: $basic->official_colors,
            emoji: $basic->emoji,
            representativeSymbol: $basic->representative_symbol,
        );
    }

    /**
     */
    private function talentSummary(WikiModel $talent): SongWikiTalentSummaryReadModel
    {
        $basic = $talent->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for Wiki.');
        }

        return new SongWikiTalentSummaryReadModel(
            wikiIdentifier: $talent->id,
            slug: $talent->slug,
            language: $talent->language,
            name: $basic->name,
            normalizedName: $basic->normalized_name,
            realName: $basic->real_name,
            normalizedRealName: $basic->normalized_real_name,
            birthday: $basic->birthday,
            agencyIdentifier: $basic->agency_identifier,
            emoji: $basic->emoji,
            representativeSymbol: $basic->representative_symbol,
            position: $basic->position,
            mbti: $basic->mbti,
            zodiacSign: $basic->zodiac_sign,
            englishLevel: $basic->english_level,
            height: $basic->height,
            bloodType: $basic->blood_type,
            fandomName: $basic->fandom_name,
        );
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    private function sectionsWithImages(array $sections): array
    {
        $imageDetails = $this->imageDetails($this->imageIdentifiers($sections));

        return array_map(fn (array $section): array => $this->sectionWithImages($section, $imageDetails), $sections);
    }

    /**
     * @param array<string, mixed> $section
     * @param array<string, array{src: ?string, alt: ?string}> $imageDetails
     * @return array<string, mixed>
     */
    private function sectionWithImages(array $section, array $imageDetails): array
    {
        if (! isset($section['contents']) || ! is_array($section['contents'])) {
            return $section;
        }

        $section['contents'] = array_map(function (mixed $content) use ($imageDetails): mixed {
            if (! is_array($content)) {
                return $content;
            }

            if (($content['type'] ?? null) === 'section') {
                return $this->sectionWithImages($content, $imageDetails);
            }

            $blockType = $content['block_type'] ?? $content['blockType'] ?? $content['type'] ?? null;
            if ($blockType === 'image') {
                $imageIdentifier = $content['image_identifier'] ?? $content['imageIdentifier'] ?? null;
                if (is_string($imageIdentifier) && isset($imageDetails[$imageIdentifier])) {
                    $content['src'] = $imageDetails[$imageIdentifier]['src'];
                    $content['alt'] = $content['alt'] ?? $imageDetails[$imageIdentifier]['alt'];
                }
            }

            if ($blockType === 'image_gallery') {
                $imageIdentifiers = $content['image_identifiers'] ?? $content['imageIdentifiers'] ?? [];
                if (is_array($imageIdentifiers)) {
                    $content['images'] = array_values(array_filter(array_map(
                        static fn (mixed $imageIdentifier): ?array => is_string($imageIdentifier) && isset($imageDetails[$imageIdentifier])
                            ? [
                                'imageIdentifier' => $imageIdentifier,
                                'src' => $imageDetails[$imageIdentifier]['src'],
                                'alt' => $imageDetails[$imageIdentifier]['alt'],
                            ]
                            : null,
                        $imageIdentifiers,
                    )));
                }
            }

            return $content;
        }, $section['contents']);

        return $section;
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<string>
     */
    private function imageIdentifiers(array $sections): array
    {
        $identifiers = [];
        foreach ($sections as $section) {
            foreach (($section['contents'] ?? []) as $content) {
                if (! is_array($content)) {
                    continue;
                }
                if (($content['type'] ?? null) === 'section') {
                    $identifiers = [...$identifiers, ...$this->imageIdentifiers([$content])];

                    continue;
                }
                $blockType = $content['block_type'] ?? $content['blockType'] ?? $content['type'] ?? null;
                if ($blockType === 'image' && is_string($content['image_identifier'] ?? null)) {
                    $identifiers[] = $content['image_identifier'];
                }
                if ($blockType === 'image_gallery' && is_array($content['image_identifiers'] ?? null)) {
                    foreach ($content['image_identifiers'] as $imageIdentifier) {
                        if (is_string($imageIdentifier)) {
                            $identifiers[] = $imageIdentifier;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($identifiers));
    }

    /**
     * @param list<string> $imageIdentifiers
     * @return array<string, array{src: ?string, alt: ?string}>
     */
    private function imageDetails(array $imageIdentifiers): array
    {
        if ($imageIdentifiers === []) {
            return [];
        }

        $details = [];
        $images = WikiImageModel::query()->whereIn('id', $imageIdentifiers)->get(['id', 'image_path', 'alt_text']);
        foreach ($images as $image) {
            $details[(string) $image->id] = [
                'src' => ImageUrl::fromPath($image->image_path),
                'alt' => $image->alt_text,
            ];
        }

        return $details;
    }
}
