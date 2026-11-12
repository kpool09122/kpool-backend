<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Service;

use Source\Wiki\Wiki\Domain\Exception\NgWordDetectedException;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageGalleryBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\QuoteBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableCell;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;

final readonly class WikiNgWordValidationService implements WikiNgWordValidationServiceInterface
{
    private string $ngWordPattern;

    public function __construct()
    {
        $wordCharacter = '[\p{L}\p{M}\p{N}_]';
        $wholeWords = self::alternatives(WikiNgWords::WHOLE_WORDS_AND_PHRASES);
        $expressions = self::alternatives(WikiNgWords::TEXT_EXPRESSIONS);
        $this->ngWordPattern = "~(?<!{$wordCharacter})(?:{$wholeWords})(?!{$wordCharacter})|(?:{$expressions})~iu";
    }

    /** @throws NgWordDetectedException */
    public function validate(
        BasicInterface $basic,
        SectionContentCollection $sections,
        ?SeoTitle $title,
        ?MetaDescription $metaDescription,
        ?SeoKeywords $keywords,
    ): void {
        $this->validateTexts([
            $title !== null ? (string) $title : null,
            $metaDescription !== null ? (string) $metaDescription : null,
            ...($keywords?->values() ?? []),
        ]);
        $this->validateBasic($basic);
        $this->validateSections($sections);
    }

    /** @throws NgWordDetectedException */
    private function validateBasic(BasicInterface $basic): void
    {
        $this->validateTexts([(string) $basic->name()]);
        $this->validateTexts(match (true) {
            $basic instanceof AgencyBasic => [(string) $basic->ceo()],
            $basic instanceof GroupBasic => [
                $basic->fandomName()->value(),
                $basic->emoji()->value(),
                $basic->representativeSymbol()->value(),
                ...array_map(static fn (Color $color): string => $color->label(), $basic->officialColors()),
            ],
            $basic instanceof SongBasic => [
                $basic->albumName(),
                (string) $basic->lyricist(),
                (string) $basic->composer(),
                (string) $basic->arranger(),
            ],
            $basic instanceof TalentBasic => [
                (string) $basic->realName(),
                $basic->fandomName()->value(),
                $basic->emoji()->value(),
                $basic->representativeSymbol()->value(),
            ],
            default => [],
        });
    }

    /** @throws NgWordDetectedException */
    private function validateSections(SectionContentCollection $sections): void
    {
        foreach ($sections->all() as $content) {
            $this->validateTexts(match (true) {
                $content instanceof Section, $content instanceof ProfileCardListBlock => [$content->title()],
                $content instanceof TextBlock => [$content->content()],
                $content instanceof QuoteBlock => [$content->content(), $content->source()],
                $content instanceof ImageBlock => [$content->caption(), $content->alt()],
                $content instanceof ImageGalleryBlock, $content instanceof EmbedBlock => [$content->caption()],
                $content instanceof ListBlock => $content->items(),
                $content instanceof TableBlock => array_map(
                    static fn (TableCell $cell): string => $cell->content(),
                    array_merge($content->headerCells() ?? [], ...array_values($content->rowCells())),
                ),
                default => [],
            });

            if ($content instanceof Section) {
                $this->validateSections($content->contents());
            }
        }
    }

    /**
     * @param array<?string> $texts
     * @throws NgWordDetectedException
     */
    private function validateTexts(array $texts): void
    {
        foreach ($texts as $text) {
            if ($text === null || $text === '') {
                continue;
            }

            $normalizedText = mb_convert_kana($text, 'asKV', 'UTF-8');
            if (preg_match($this->ngWordPattern, $normalizedText) === 1) {
                throw new NgWordDetectedException();
            }
        }
    }

    /** @param list<non-empty-string> $terms */
    private static function alternatives(array $terms): string
    {
        return implode('|', array_map(
            static fn (string $term): string => str_replace(' ', '\s+', preg_quote($term, '~')),
            $terms,
        ));
    }
}
