<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\DraftWikiSongBasic as DraftWikiSongBasicModel;
use Application\Models\Wiki\Wiki as WikiModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMySongDraftWiki\GetMySongDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMySongDraftWiki\GetMySongDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SongWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\SongWikiTalentSummaryReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiGroupSummaryReadModel;

readonly class GetMySongDraftWiki implements GetMySongDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMySongDraftWikiInputPort $input): DraftWikiReadModel
    {
        $slug = $input->slug();
        $language = $input->language();
        $editorIdentifier = $input->editorIdentifier();

        $model = DraftWikiModel::query()
            ->select('draft_wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['songBasic.groups.groupBasic', 'songBasic.talents.talentBasic', 'publishedWiki'])
            ->where('draft_wikis.resource_type', ResourceType::SONG->value)
            ->where('draft_wikis.language', $language->value)
            ->where('draft_wikis.slug', (string) $slug)
            ->where('draft_wikis.editor_id', (string) $editorIdentifier)
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft song wiki not found for slug: {$slug}, language: {$language->value}, and editor: {$editorIdentifier}");
        }

        return $this->readModel($model);
    }

    private function readModel(DraftWikiModel $model): DraftWikiReadModel
    {
        $basic = $this->songBasic($model->songBasic);

        return new DraftWikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::SONG->value,
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
            status: $model->status,
        );
    }

    private function songBasic(?DraftWikiSongBasicModel $basic): DraftWikiSongBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('SongBasic not found for DraftWiki.');
        }

        return $basic;
    }

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
        return WikiSectionReadModelBuilder::build($sections);
    }
}
