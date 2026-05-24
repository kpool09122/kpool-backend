<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic as WikiAgencyBasicModel;
use Application\Models\Wiki\WikiImage as WikiImageModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\AgencyWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki\GetAgencyWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki\GetAgencyWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

readonly class GetAgencyWiki implements GetAgencyWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetAgencyWikiInputPort $input): WikiReadModel
    {
        $model = WikiModel::query()
            ->select('wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->with(['agencyBasic'])
            ->where('wikis.resource_type', ResourceType::AGENCY->value)
            ->where('wikis.language', $input->language()->value)
            ->where('wikis.slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $this->agencyBasic($model->agencyBasic);

        return new WikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::AGENCY->value,
            version: $model->version,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $model->image_identifier,
                'src' => ImageUrl::fromPath($model->getAttribute('hero_image_path')),
                'alt' => $model->getAttribute('hero_image_alt_text'),
            ],
            basic: new AgencyWikiBasicReadModel(
                name: $basic->name,
                normalizedName: $basic->normalized_name,
                ceo: $basic->ceo,
                normalizedCeo: $basic->normalized_ceo,
                foundedIn: $basic->founded_in,
                parentAgencyIdentifier: $basic->parent_agency_identifier,
                status: $basic->status,
                officialWebsite: $basic->official_website,
                socialLinks: $basic->social_links,
            ),
            sections: $this->sectionsWithImages($model->sections),
        );
    }

    private function agencyBasic(?WikiAgencyBasicModel $basic): WikiAgencyBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('AgencyBasic not found for Wiki.');
        }

        return $basic;
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
}
