<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiImage as WikiImageModel;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Wiki\Application\UseCase\Query\RelatedProfileReadModel;

final readonly class WikiSectionReadModelBuilder
{
    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    public static function build(array $sections): array
    {
        return (new self())->sectionsWithDetails($sections);
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    private function sectionsWithDetails(array $sections): array
    {
        $imageDetails = $this->imageDetails($this->imageIdentifiers($sections));
        $profileDetails = $this->profileDetails($this->profileWikiIdentifiers($sections));

        return array_map(fn (array $section): array => $this->sectionWithDetails($section, $imageDetails, $profileDetails), $sections);
    }

    /**
     * @param array<string, mixed> $section
     * @param array<string, array{src: ?string, alt: ?string}> $imageDetails
     * @param array<string, array<string, mixed>> $profileDetails
     * @return array<string, mixed>
     */
    private function sectionWithDetails(array $section, array $imageDetails, array $profileDetails): array
    {
        if (! isset($section['contents']) || ! is_array($section['contents'])) {
            return $section;
        }

        $section['contents'] = array_map(function (mixed $content) use ($imageDetails, $profileDetails): mixed {
            if (! is_array($content)) {
                return $content;
            }

            if (($content['type'] ?? null) === 'section') {
                return $this->sectionWithDetails($content, $imageDetails, $profileDetails);
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

            if ($blockType === 'profile_card_list') {
                $wikiIdentifiers = $this->stringList($content['wiki_identifiers'] ?? $content['wikiIdentifiers'] ?? []);
                $profiles = array_values(array_filter(array_map(
                    static fn (string $wikiIdentifier): ?array => $profileDetails[$wikiIdentifier] ?? null,
                    $wikiIdentifiers,
                )));

                $content['wikiIdentifiers'] = $content['wikiIdentifiers'] ?? $wikiIdentifiers;
                $content['profiles'] = $profiles;
                $content['relatedResourceType'] = $content['relatedResourceType'] ?? $content['related_resource_type'] ?? $this->relatedResourceType($profiles);
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
                $imageIdentifier = $content['image_identifier'] ?? $content['imageIdentifier'] ?? null;
                if ($blockType === 'image' && is_string($imageIdentifier)) {
                    $identifiers[] = $imageIdentifier;
                }

                $imageIdentifiers = $content['image_identifiers'] ?? $content['imageIdentifiers'] ?? null;
                if ($blockType === 'image_gallery' && is_array($imageIdentifiers)) {
                    foreach ($imageIdentifiers as $imageIdentifier) {
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

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<string>
     */
    private function profileWikiIdentifiers(array $sections): array
    {
        $identifiers = [];
        foreach ($sections as $section) {
            foreach (($section['contents'] ?? []) as $content) {
                if (! is_array($content)) {
                    continue;
                }

                if (($content['type'] ?? null) === 'section') {
                    $identifiers = [...$identifiers, ...$this->profileWikiIdentifiers([$content])];

                    continue;
                }

                $blockType = $content['block_type'] ?? $content['blockType'] ?? $content['type'] ?? null;
                if ($blockType === 'profile_card_list') {
                    $identifiers = [...$identifiers, ...$this->stringList($content['wiki_identifiers'] ?? $content['wikiIdentifiers'] ?? [])];
                }
            }
        }

        return array_values(array_unique($identifiers));
    }

    /**
     * @param list<string> $wikiIdentifiers
     * @return array<string, array<string, mixed>>
     */
    private function profileDetails(array $wikiIdentifiers): array
    {
        if ($wikiIdentifiers === []) {
            return [];
        }

        $profiles = WikiModel::query()
            ->select(
                'wikis.*',
                'wiki_images.image_path as image_path',
                'wiki_images.alt_text as image_alt_text',
            )
            ->selectRaw('COALESCE(wiki_agency_basics.name, wiki_group_basics.name, wiki_talent_basics.name, wiki_song_basics.name) as profile_name')
            ->selectRaw('COALESCE(wiki_agency_basics.normalized_name, wiki_group_basics.normalized_name, wiki_talent_basics.normalized_name, wiki_song_basics.normalized_name) as profile_normalized_name')
            ->leftJoin('wiki_agency_basics', 'wiki_agency_basics.wiki_id', '=', 'wikis.id')
            ->leftJoin('wiki_group_basics', 'wiki_group_basics.wiki_id', '=', 'wikis.id')
            ->leftJoin('wiki_talent_basics', 'wiki_talent_basics.wiki_id', '=', 'wikis.id')
            ->leftJoin('wiki_song_basics', 'wiki_song_basics.wiki_id', '=', 'wikis.id')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->whereIn('wikis.id', $wikiIdentifiers)
            ->get();

        $details = [];
        foreach ($profiles as $profile) {
            $details[(string) $profile->id] = (new RelatedProfileReadModel(
                wikiIdentifier: $profile->id,
                slug: $profile->slug,
                language: $profile->language,
                resourceType: $profile->resource_type,
                name: (string) $profile->getAttribute('profile_name'),
                normalizedName: (string) $profile->getAttribute('profile_normalized_name'),
                imageIdentifier: $profile->image_identifier,
                imageUrl: ImageUrl::fromPath($profile->getAttribute('image_path')),
                imageAltText: $profile->getAttribute('image_alt_text'),
            ))->toArray();
        }

        return $details;
    }

    /**
     * @param array<int, array<string, mixed>> $profiles
     */
    private function relatedResourceType(array $profiles): ?string
    {
        $resourceTypes = array_values(array_unique(array_filter(array_map(
            static fn (array $profile): ?string => is_string($profile['resourceType'] ?? null) ? $profile['resourceType'] : null,
            $profiles,
        ))));

        return count($resourceTypes) === 1 ? $resourceTypes[0] : null;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): ?string => is_string($item) ? $item : null,
            $value,
        )));
    }
}
