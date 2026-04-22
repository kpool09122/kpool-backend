<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\DraftWikiAgencyBasic as DraftWikiAgencyBasicModel;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\AgencyDraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInterface;

readonly class GetAgencyDraftWiki implements GetAgencyDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetAgencyDraftWikiInputPort $input): AgencyDraftWikiReadModel
    {
        $model = DraftWikiModel::query()
            ->with(['agencyBasic', 'publishedWiki'])
            ->where('resource_type', ResourceType::AGENCY->value)
            ->where('language', $input->language()->value)
            ->where('slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $this->agencyBasic($model->agencyBasic);

        return new AgencyDraftWikiReadModel(
            wikiIdentifier: $model->id,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::AGENCY->value,
            version: $model->publishedWiki->version,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $basic->logo_image_identifier,
            ],
            basic: [
                'name' => $basic->name,
                'normalizedName' => $basic->normalized_name,
                'ceo' => $basic->ceo,
                'normalizedCeo' => $basic->normalized_ceo,
                'foundedIn' => $basic->founded_in,
                'parentAgencyIdentifier' => $basic->parent_agency_identifier,
                'status' => $basic->status,
                'logoImageIdentifier' => $basic->logo_image_identifier,
                'officialWebsite' => $basic->official_website,
                'socialLinks' => $basic->social_links,
            ],
            sections: $model->sections,
        );
    }

    private function agencyBasic(?DraftWikiAgencyBasicModel $basic): DraftWikiAgencyBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('AgencyBasic not found for DraftWiki.');
        }

        return $basic;
    }
}
