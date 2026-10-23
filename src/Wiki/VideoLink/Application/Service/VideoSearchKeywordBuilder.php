<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Service;

use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;

readonly class VideoSearchKeywordBuilder
{
    public function __construct(
        private WikiRepositoryInterface $wikiRepository,
    ) {
    }

    public function build(Wiki $wiki): string
    {
        $basic = $wiki->basic();
        $terms = [(string) $basic->name()];
        $relatedIdentifiers = match (true) {
            $basic instanceof SongBasic => [...$basic->groupIdentifiers(), ...$basic->talentIdentifiers()],
            $basic instanceof TalentBasic => $basic->groupIdentifiers(),
            default => [],
        };

        foreach ($relatedIdentifiers as $identifier) {
            $relatedWiki = $this->wikiRepository->findById($identifier);
            if ($relatedWiki !== null) {
                $terms[] = (string) $relatedWiki->basic()->name();
            }
        }

        $terms[] = 'k-pop';

        return implode(' ', array_unique($terms));
    }
}
