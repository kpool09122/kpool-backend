<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

final class WikiBasicReadModelFactory
{
    /**
     * @param array<string, mixed> $basic
     */
    public static function group(array $basic): GroupWikiBasicReadModel
    {
        return new GroupWikiBasicReadModel(
            name: $basic['name'],
            normalizedName: $basic['normalizedName'],
            agencyIdentifier: $basic['agencyIdentifier'],
            agency: isset($basic['agency']) && is_array($basic['agency']) ? self::agencySummary($basic['agency']) : null,
            groupType: $basic['groupType'],
            status: $basic['status'],
            generation: $basic['generation'],
            debutDate: $basic['debutDate'],
            disbandDate: $basic['disbandDate'],
            fandomName: $basic['fandomName'],
            officialColors: $basic['officialColors'],
            emoji: $basic['emoji'],
            representativeSymbol: $basic['representativeSymbol'],
        );
    }

    /**
     * @param array<string, mixed> $basic
     */
    public static function talent(array $basic): TalentWikiBasicReadModel
    {
        return new TalentWikiBasicReadModel(
            name: $basic['name'],
            normalizedName: $basic['normalizedName'],
            realName: $basic['realName'],
            normalizedRealName: $basic['normalizedRealName'],
            birthday: $basic['birthday'],
            agencyIdentifier: $basic['agencyIdentifier'],
            agency: isset($basic['agency']) && is_array($basic['agency']) ? self::agencySummary($basic['agency']) : null,
            emoji: $basic['emoji'],
            representativeSymbol: $basic['representativeSymbol'],
            position: $basic['position'],
            mbti: $basic['mbti'],
            zodiacSign: $basic['zodiacSign'],
            englishLevel: $basic['englishLevel'],
            height: $basic['height'],
            bloodType: $basic['bloodType'],
            fandomName: $basic['fandomName'],
            groups: array_map(
                static fn (array|TalentWikiGroupSummaryReadModel $group): TalentWikiGroupSummaryReadModel => is_array($group)
                    ? self::groupSummary($group)
                    : $group,
                $basic['groups'],
            ),
        );
    }

    /**
     * @param array<string, mixed> $basic
     */
    public static function song(array $basic): SongWikiBasicReadModel
    {
        return new SongWikiBasicReadModel(
            name: $basic['name'],
            normalizedName: $basic['normalizedName'],
            songType: $basic['songType'],
            genres: $basic['genres'],
            agencyIdentifier: $basic['agencyIdentifier'],
            agency: isset($basic['agency']) && is_array($basic['agency']) ? self::agencySummary($basic['agency']) : null,
            releaseDate: $basic['releaseDate'],
            albumName: $basic['albumName'],
            lyricist: $basic['lyricist'],
            normalizedLyricist: $basic['normalizedLyricist'],
            composer: $basic['composer'],
            normalizedComposer: $basic['normalizedComposer'],
            arranger: $basic['arranger'],
            normalizedArranger: $basic['normalizedArranger'],
            groups: array_map(
                static fn (array|TalentWikiGroupSummaryReadModel $group): TalentWikiGroupSummaryReadModel => is_array($group)
                    ? self::groupSummary($group)
                    : $group,
                $basic['groups'],
            ),
            talents: array_map(
                static fn (array|SongWikiTalentSummaryReadModel $talent): SongWikiTalentSummaryReadModel => is_array($talent)
                    ? self::talentSummary($talent)
                    : $talent,
                $basic['talents'],
            ),
        );
    }

    /**
     * @param array<string, mixed> $basic
     */
    public static function agency(array $basic): AgencyWikiBasicReadModel
    {
        return new AgencyWikiBasicReadModel(
            name: $basic['name'],
            normalizedName: $basic['normalizedName'],
            ceo: $basic['ceo'],
            normalizedCeo: $basic['normalizedCeo'],
            foundedIn: $basic['foundedIn'],
            parentAgencyIdentifier: $basic['parentAgencyIdentifier'],
            status: $basic['status'],
            officialWebsite: $basic['officialWebsite'],
            socialLinks: $basic['socialLinks'],
        );
    }

    /**
     * @param array<string, mixed> $agency
     */
    private static function agencySummary(array $agency): WikiAgencySummaryReadModel
    {
        return new WikiAgencySummaryReadModel(
            wikiIdentifier: $agency['wikiIdentifier'],
            slug: $agency['slug'],
            language: $agency['language'],
            name: $agency['name'],
            normalizedName: $agency['normalizedName'],
        );
    }

    /**
     * @param array<string, mixed> $group
     */
    private static function groupSummary(array $group): TalentWikiGroupSummaryReadModel
    {
        return new TalentWikiGroupSummaryReadModel(
            wikiIdentifier: $group['wikiIdentifier'],
            slug: $group['slug'],
            language: $group['language'],
            name: $group['name'],
            normalizedName: $group['normalizedName'],
            agencyIdentifier: $group['agencyIdentifier'],
            groupType: $group['groupType'],
            status: $group['status'],
            generation: $group['generation'],
            debutDate: $group['debutDate'],
            disbandDate: $group['disbandDate'],
            fandomName: $group['fandomName'],
            officialColors: $group['officialColors'],
            emoji: $group['emoji'],
            representativeSymbol: $group['representativeSymbol'],
        );
    }

    /**
     * @param array<string, mixed> $talent
     */
    private static function talentSummary(array $talent): SongWikiTalentSummaryReadModel
    {
        return new SongWikiTalentSummaryReadModel(
            wikiIdentifier: $talent['wikiIdentifier'],
            slug: $talent['slug'],
            language: $talent['language'],
            name: $talent['name'],
            normalizedName: $talent['normalizedName'],
            realName: $talent['realName'],
            normalizedRealName: $talent['normalizedRealName'],
            birthday: $talent['birthday'],
            agencyIdentifier: $talent['agencyIdentifier'],
            emoji: $talent['emoji'],
            representativeSymbol: $talent['representativeSymbol'],
            position: $talent['position'],
            mbti: $talent['mbti'],
            zodiacSign: $talent['zodiacSign'],
            englishLevel: $talent['englishLevel'],
            height: $talent['height'],
            bloodType: $talent['bloodType'],
            fandomName: $talent['fandomName'],
        );
    }
}
