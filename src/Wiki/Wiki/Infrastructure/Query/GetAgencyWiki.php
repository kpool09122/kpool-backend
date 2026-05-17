<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic as WikiAgencyBasicModel;
use InvalidArgumentException;
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
            ->with(['agencyBasic'])
            ->where('resource_type', ResourceType::AGENCY->value)
            ->where('language', $input->language()->value)
            ->where('slug', (string) $input->slug())
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
            sections: $model->sections,
        );
    }

    private function agencyBasic(?WikiAgencyBasicModel $basic): WikiAgencyBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('AgencyBasic not found for Wiki.');
        }

        return $basic;
    }
}
