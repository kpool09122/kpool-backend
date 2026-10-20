<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\DTO;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final readonly class MaterialSearchContext
{
    /** @param list<RelatedWikiContext> $relatedWikis */
    public function __construct(public string $title, public ResourceType $resourceType, public Language $language, public array $relatedWikis)
    {
        if (trim($this->title) === '' || mb_strlen($this->title) > 255 || count($relatedWikis) > 100
            || ! in_array($resourceType, [ResourceType::AGENCY, ResourceType::GROUP, ResourceType::TALENT, ResourceType::SONG], true)) {
            throw new InvalidArgumentException('Invalid material search context');
        }
    }
}
