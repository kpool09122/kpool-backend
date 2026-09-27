<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

use InvalidArgumentException;
use Source\Shared\Domain\Support\TypedValue;

final class WikiBasicReadModelFactory
{
    /**
     * @param array<string, mixed> $basic
     */
    public static function group(array $basic): GroupWikiBasicReadModel
    {
        return new GroupWikiBasicReadModel(
            name: TypedValue::string($basic['name']),
            normalizedName: TypedValue::string($basic['normalizedName']),
            agencyIdentifier: TypedValue::nullableString($basic['agencyIdentifier']),
            agency: isset($basic['agency']) && is_array($basic['agency']) ? self::agencySummary($basic['agency']) : null,
            groupType: TypedValue::nullableString($basic['groupType']),
            status: TypedValue::nullableString($basic['status']),
            generation: TypedValue::nullableString($basic['generation']),
            debutDate: TypedValue::nullableString($basic['debutDate']),
            disbandDate: TypedValue::nullableString($basic['disbandDate']),
            fandomName: TypedValue::string($basic['fandomName']),
            officialColors: self::officialColors($basic['officialColors']),
            emoji: TypedValue::string($basic['emoji']),
            representativeSymbol: TypedValue::string($basic['representativeSymbol']),
        );
    }

    /**
     * @param array<string, mixed> $basic
     */
    public static function talent(array $basic): TalentWikiBasicReadModel
    {
        return new TalentWikiBasicReadModel(
            name: TypedValue::string($basic['name']),
            normalizedName: TypedValue::string($basic['normalizedName']),
            realName: TypedValue::string($basic['realName']),
            normalizedRealName: TypedValue::string($basic['normalizedRealName']),
            birthday: TypedValue::nullableString($basic['birthday']),
            agencyIdentifier: TypedValue::nullableString($basic['agencyIdentifier']),
            agency: isset($basic['agency']) && is_array($basic['agency']) ? self::agencySummary($basic['agency']) : null,
            emoji: TypedValue::string($basic['emoji']),
            representativeSymbol: TypedValue::string($basic['representativeSymbol']),
            position: TypedValue::string($basic['position']),
            mbti: TypedValue::nullableString($basic['mbti']),
            zodiacSign: TypedValue::nullableString($basic['zodiacSign']),
            englishLevel: TypedValue::nullableString($basic['englishLevel']),
            height: TypedValue::nullableStringOrInt($basic['height']),
            bloodType: TypedValue::nullableString($basic['bloodType']),
            fandomName: TypedValue::string($basic['fandomName']),
            groups: array_map(
                static fn (mixed $group): TalentWikiGroupSummaryReadModel => is_array($group)
                    ? self::groupSummary($group)
                    : self::groupSummaryModel($group),
                array_values(TypedValue::array($basic['groups'])),
            ),
        );
    }

    /**
     * @param array<string, mixed> $basic
     */
    public static function song(array $basic): SongWikiBasicReadModel
    {
        return new SongWikiBasicReadModel(
            name: TypedValue::string($basic['name']),
            normalizedName: TypedValue::string($basic['normalizedName']),
            songType: TypedValue::nullableString($basic['songType']),
            genres: array_values(TypedValue::stringArray($basic['genres'])),
            agencyIdentifier: TypedValue::nullableString($basic['agencyIdentifier']),
            agency: isset($basic['agency']) && is_array($basic['agency']) ? self::agencySummary($basic['agency']) : null,
            releaseDate: TypedValue::nullableString($basic['releaseDate']),
            albumName: TypedValue::nullableString($basic['albumName']),
            lyricist: TypedValue::string($basic['lyricist']),
            normalizedLyricist: TypedValue::string($basic['normalizedLyricist']),
            composer: TypedValue::string($basic['composer']),
            normalizedComposer: TypedValue::string($basic['normalizedComposer']),
            arranger: TypedValue::string($basic['arranger']),
            normalizedArranger: TypedValue::string($basic['normalizedArranger']),
            groups: array_map(
                static fn (mixed $group): TalentWikiGroupSummaryReadModel => is_array($group)
                    ? self::groupSummary($group)
                    : self::groupSummaryModel($group),
                array_values(TypedValue::array($basic['groups'])),
            ),
            talents: array_map(
                static fn (mixed $talent): SongWikiTalentSummaryReadModel => is_array($talent)
                    ? self::talentSummary($talent)
                    : self::talentSummaryModel($talent),
                array_values(TypedValue::array($basic['talents'])),
            ),
        );
    }

    /**
     * @param array<string, mixed> $basic
     */
    public static function agency(array $basic): AgencyWikiBasicReadModel
    {
        return new AgencyWikiBasicReadModel(
            name: TypedValue::string($basic['name']),
            normalizedName: TypedValue::string($basic['normalizedName']),
            ceo: TypedValue::string($basic['ceo']),
            normalizedCeo: TypedValue::string($basic['normalizedCeo']),
            foundedIn: TypedValue::nullableString($basic['foundedIn']),
            parentAgencyIdentifier: TypedValue::nullableString($basic['parentAgencyIdentifier']),
            status: TypedValue::nullableString($basic['status']),
            officialWebsite: TypedValue::nullableString($basic['officialWebsite']),
            socialLinks: array_values(TypedValue::stringArray($basic['socialLinks'])),
        );
    }

    /**
     * @param array<array-key, mixed> $agency
     */
    private static function agencySummary(array $agency): WikiAgencySummaryReadModel
    {
        return new WikiAgencySummaryReadModel(
            wikiIdentifier: TypedValue::string($agency['wikiIdentifier']),
            slug: TypedValue::string($agency['slug']),
            language: TypedValue::string($agency['language']),
            name: TypedValue::string($agency['name']),
            normalizedName: TypedValue::string($agency['normalizedName']),
        );
    }

    /**
     * @param array<array-key, mixed> $group
     */
    private static function groupSummary(array $group): TalentWikiGroupSummaryReadModel
    {
        return new TalentWikiGroupSummaryReadModel(
            wikiIdentifier: TypedValue::string($group['wikiIdentifier']),
            slug: TypedValue::string($group['slug']),
            language: TypedValue::string($group['language']),
            name: TypedValue::string($group['name']),
            normalizedName: TypedValue::string($group['normalizedName']),
            agencyIdentifier: TypedValue::nullableString($group['agencyIdentifier']),
            groupType: TypedValue::nullableString($group['groupType']),
            status: TypedValue::nullableString($group['status']),
            generation: TypedValue::nullableString($group['generation']),
            debutDate: TypedValue::nullableString($group['debutDate']),
            disbandDate: TypedValue::nullableString($group['disbandDate']),
            fandomName: TypedValue::string($group['fandomName']),
            officialColors: self::officialColors($group['officialColors']),
            emoji: TypedValue::string($group['emoji']),
            representativeSymbol: TypedValue::string($group['representativeSymbol']),
        );
    }

    /**
     * @param array<array-key, mixed> $talent
     */
    private static function talentSummary(array $talent): SongWikiTalentSummaryReadModel
    {
        return new SongWikiTalentSummaryReadModel(
            wikiIdentifier: TypedValue::string($talent['wikiIdentifier']),
            slug: TypedValue::string($talent['slug']),
            language: TypedValue::string($talent['language']),
            name: TypedValue::string($talent['name']),
            normalizedName: TypedValue::string($talent['normalizedName']),
            realName: TypedValue::string($talent['realName']),
            normalizedRealName: TypedValue::string($talent['normalizedRealName']),
            birthday: TypedValue::nullableString($talent['birthday']),
            agencyIdentifier: TypedValue::nullableString($talent['agencyIdentifier']),
            emoji: TypedValue::string($talent['emoji']),
            representativeSymbol: TypedValue::string($talent['representativeSymbol']),
            position: TypedValue::string($talent['position']),
            mbti: TypedValue::nullableString($talent['mbti']),
            zodiacSign: TypedValue::nullableString($talent['zodiacSign']),
            englishLevel: TypedValue::nullableString($talent['englishLevel']),
            height: TypedValue::nullableStringOrInt($talent['height']),
            bloodType: TypedValue::nullableString($talent['bloodType']),
            fandomName: TypedValue::string($talent['fandomName']),
        );
    }

    private static function groupSummaryModel(mixed $value): TalentWikiGroupSummaryReadModel
    {
        if (! $value instanceof TalentWikiGroupSummaryReadModel) {
            throw new InvalidArgumentException('Expected a group summary.');
        }

        return $value;
    }

    private static function talentSummaryModel(mixed $value): SongWikiTalentSummaryReadModel
    {
        if (! $value instanceof SongWikiTalentSummaryReadModel) {
            throw new InvalidArgumentException('Expected a talent summary.');
        }

        return $value;
    }

    /** @return list<array{colorCode: string, label: string}> */
    private static function officialColors(mixed $value): array
    {
        $colors = [];
        foreach (TypedValue::array($value) as $color) {
            $color = TypedValue::array($color);
            $colors[] = ['colorCode' => TypedValue::string($color['colorCode']), 'label' => TypedValue::string($color['label'])];
        }

        return $colors;
    }
}
