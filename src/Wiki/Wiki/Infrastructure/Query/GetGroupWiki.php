<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiGroupBasic as WikiGroupBasicModel;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

readonly class GetGroupWiki implements GetGroupWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetGroupWikiInputPort $input): WikiReadModel
    {
        $model = WikiModel::query()
            ->with(['groupBasic'])
            ->where('resource_type', ResourceType::GROUP->value)
            ->where('language', $input->language()->value)
            ->where('slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $this->groupBasic($model->groupBasic);

        return new WikiReadModel(
            wikiIdentifier: $model->id,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::GROUP->value,
            version: $model->version,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $basic->main_image_identifier,
            ],
            basic: [
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
            ],
            sections: $model->sections,
        );
    }

    private function groupBasic(?WikiGroupBasicModel $basic): WikiGroupBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for Wiki.');
        }

        return $basic;
    }
}
