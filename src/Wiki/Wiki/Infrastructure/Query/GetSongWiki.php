<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiSongBasic as WikiSongBasicModel;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongWiki\GetSongWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongWiki\GetSongWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

readonly class GetSongWiki implements GetSongWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetSongWikiInputPort $input): WikiReadModel
    {
        $model = WikiModel::query()
            ->with(['songBasic.groups.groupBasic', 'songBasic.talents.talentBasic'])
            ->where('resource_type', ResourceType::SONG->value)
            ->where('language', $input->language()->value)
            ->where('slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $this->songBasic($model->songBasic);

        return new WikiReadModel(
            wikiIdentifier: $model->id,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::SONG->value,
            version: $model->version,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $basic->cover_image_identifier,
            ],
            basic: [
                'name' => $basic->name,
                'normalizedName' => $basic->normalized_name,
                'songType' => $basic->song_type,
                'genres' => $basic->genres,
                'agencyIdentifier' => $basic->agency_identifier,
                'releaseDate' => $basic->release_date,
                'albumName' => $basic->album_name,
                'coverImageIdentifier' => $basic->cover_image_identifier,
                'lyricist' => $basic->lyricist,
                'normalizedLyricist' => $basic->normalized_lyricist,
                'composer' => $basic->composer,
                'normalizedComposer' => $basic->normalized_composer,
                'arranger' => $basic->arranger,
                'normalizedArranger' => $basic->normalized_arranger,
                'groups' => $basic->groups->map(fn (WikiModel $group) => $this->groupToArray($group))->values()->all(),
                'talents' => $basic->talents->map(fn (WikiModel $talent) => $this->talentToArray($talent))->values()->all(),
            ],
            sections: $model->sections,
        );
    }

    private function songBasic(?WikiSongBasicModel $basic): WikiSongBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('SongBasic not found for Wiki.');
        }

        return $basic;
    }

    /**
     * @return array<string, mixed>
     */
    private function groupToArray(WikiModel $group): array
    {
        $basic = $group->groupBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for Wiki.');
        }

        return [
            'wikiIdentifier' => $group->id,
            'slug' => $group->slug,
            'language' => $group->language,
            'name' => $basic->name,
            'normalizedName' => $basic->normalized_name,
            'agencyIdentifier' => $basic->agency_identifier,
            'groupType' => $basic->group_type,
            'status' => $basic->status,
            'generation' => $basic->generation,
            'debutDate' => $basic->debut_date,
            'disbandDate' => $basic->disband_date,
            'fandomName' => $basic->fandom_name,
            'officialColors' => $basic->official_colors,
            'emoji' => $basic->emoji,
            'representativeSymbol' => $basic->representative_symbol,
            'mainImageIdentifier' => $basic->main_image_identifier,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function talentToArray(WikiModel $talent): array
    {
        $basic = $talent->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for Wiki.');
        }

        return [
            'wikiIdentifier' => $talent->id,
            'slug' => $talent->slug,
            'language' => $talent->language,
            'name' => $basic->name,
            'normalizedName' => $basic->normalized_name,
            'realName' => $basic->real_name,
            'normalizedRealName' => $basic->normalized_real_name,
            'birthday' => $basic->birthday,
            'agencyIdentifier' => $basic->agency_identifier,
            'emoji' => $basic->emoji,
            'representativeSymbol' => $basic->representative_symbol,
            'position' => $basic->position,
            'mbti' => $basic->mbti,
            'zodiacSign' => $basic->zodiac_sign,
            'englishLevel' => $basic->english_level,
            'height' => $basic->height,
            'bloodType' => $basic->blood_type,
            'fandomName' => $basic->fandom_name,
            'profileImageIdentifier' => $basic->profile_image_identifier,
        ];
    }
}
