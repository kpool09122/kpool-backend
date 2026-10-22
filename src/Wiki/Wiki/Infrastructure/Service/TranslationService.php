<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use InvalidArgumentException;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Application\Service\TranslatedWikiData;
use Source\Wiki\Wiki\Application\Service\TranslationServiceInterface;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Arranger;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Position;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;
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
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentInterface;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;

readonly class TranslationService implements TranslationServiceInterface
{
    private const int ALBUM_NAME_MAX_LENGTH = 255;

    public function __construct(
        private GoogleTranslateClient $googleTranslateClient,
    ) {
    }

    public function translateWiki(Wiki $wiki, Language $targetLanguage): TranslatedWikiData
    {
        $texts = [];
        $this->mapWikiTexts($wiki, $targetLanguage, static function (string $text, ?int $maxLength) use (&$texts): string {
            if (trim($text) !== '') {
                $texts[] = $text;
            }

            return $text;
        });

        if ($texts === []) {
            return $this->mapWikiTexts($wiki, $targetLanguage, static fn (string $text, ?int $maxLength): string => $text);
        }

        $translations = $this->googleTranslateClient->translateTexts(new TranslateTextsRequest(
            texts: $texts,
            targetLanguage: $targetLanguage->value,
        ))->translatedTexts();
        $offset = 0;

        return $this->mapWikiTexts($wiki, $targetLanguage, static function (string $text, ?int $maxLength) use ($translations, &$offset): string {
            if (trim($text) === '') {
                return $text;
            }

            $translated = $translations[$offset++] ?? $text;
            if (trim($translated) === '') {
                return $text;
            }

            return $maxLength === null ? $translated : mb_substr(trim($translated), 0, $maxLength);
        });
    }

    /**
     * @param callable(string, ?int): string $translate
     */
    private function mapWikiTexts(Wiki $wiki, Language $targetLanguage, callable $translate): TranslatedWikiData
    {
        $basic = $this->mapBasic($wiki->basic(), $translate);
        $sections = $this->mapSections($wiki->sections(), $targetLanguage, $translate);
        $title = $wiki->title();
        $description = $wiki->metaDescription();
        $keywords = $wiki->keywords();

        return new TranslatedWikiData(
            translatedBasic: $basic,
            translatedSections: $sections,
            translatedTitle: $title === null ? null : new SeoTitle($translate((string) $title, SeoTitle::MAX_LENGTH)),
            translatedMetaDescription: $description === null ? null : new MetaDescription($translate((string) $description, MetaDescription::MAX_LENGTH)),
            translatedKeywords: $keywords === null ? null : new SeoKeywords(array_map(
                static fn (string $keyword): string => $translate($keyword, SeoKeywords::MAX_KEYWORD_LENGTH),
                $keywords->values(),
            )),
        );
    }

    /**
     * @param callable(string, ?int): string $translate
     */
    private function mapBasic(BasicInterface $basic, callable $translate): BasicInterface
    {
        $data = $basic->toArray();
        $fields = match ($basic->getBasicType()) {
            'talent' => [
                'name' => Name::MAX_LENGTH,
                'real_name' => RealName::MAX_LENGTH,
                'representative_symbol' => RepresentativeSymbol::MAX_LENGTH,
                'position' => Position::MAX_LENGTH,
                'fandom_name' => FandomName::MAX_LENGTH,
            ],
            'agency' => ['name' => Name::MAX_LENGTH, 'ceo' => CEO::MAX_LENGTH],
            'group' => [
                'name' => Name::MAX_LENGTH,
                'representative_symbol' => RepresentativeSymbol::MAX_LENGTH,
                'fandom_name' => FandomName::MAX_LENGTH,
            ],
            'song' => [
                'name' => Name::MAX_LENGTH,
                'lyricist' => Lyricist::MAX_LENGTH,
                'composer' => Composer::MAX_LENGTH,
                'arranger' => Arranger::MAX_LENGTH,
                'album_name' => self::ALBUM_NAME_MAX_LENGTH,
            ],
            default => throw new InvalidArgumentException('Unsupported basic type: ' . $basic->getBasicType()),
        };
        foreach ($fields as $key => $maxLength) {
            if (isset($data[$key])) {
                $data[$key] = $translate(TypedValue::string($data[$key]), $maxLength);
            }
        }
        if ($basic instanceof GroupBasic) {
            $data['official_colors'] = array_map(static function (mixed $color) use ($translate): array {
                $colorData = TypedValue::array($color);
                $colorData['label'] = $translate(TypedValue::string($colorData['label']), Color::MAX_LABEL_LENGTH);

                return $colorData;
            }, TypedValue::array($data['official_colors']));
        }

        $storedBasicType = TypedValue::string($data['type']);

        return match ($storedBasicType) {
            'talent' => TalentBasic::fromArray($data),
            'agency' => AgencyBasic::fromArray($data),
            'group' => GroupBasic::fromArray($data),
            'song' => SongBasic::fromArray($data),
            default => throw new InvalidArgumentException('Unsupported basic type: ' . $storedBasicType),
        };
    }

    /**
     * @param callable(string, ?int): string $translate
     */
    private function mapSections(SectionContentCollection $sections, Language $targetLanguage, callable $translate): SectionContentCollection
    {
        return new SectionContentCollection(array_map(
            fn (SectionContentInterface $content): SectionContentInterface => $this->mapContent($content, $targetLanguage, $translate),
            $sections->all(),
        ));
    }

    /**
     * @param callable(string, ?int): string $translate
     */
    private function mapContent(SectionContentInterface $content, Language $targetLanguage, callable $translate): SectionContentInterface
    {
        if ($content instanceof Section) {
            $title = $translate($content->title(), null);

            return new Section(
                $targetLanguage === Language::ENGLISH ? ucfirst($title) : $title,
                $content->displayOrder(),
                $this->mapSections($content->contents(), $targetLanguage, $translate),
                $content->depth(),
            );
        }
        if ($content instanceof TextBlock) {
            return new TextBlock($content->displayOrder(), $translate($content->content(), null));
        }
        if ($content instanceof QuoteBlock) {
            return new QuoteBlock($content->displayOrder(), $translate($content->content(), null), $this->mapOptionalText($content->source(), $translate));
        }
        if ($content instanceof ListBlock) {
            return new ListBlock($content->displayOrder(), $content->listType(), array_map(
                static fn (string $item): string => $translate($item, null),
                $content->items(),
            ));
        }
        if ($content instanceof TableBlock) {
            $headers = $this->mapTableCells($content->headerCells(), $translate);
            $rows = array_map(fn (array $row): array => $this->mapTableCells($row, $translate) ?? [], $content->rowCells());

            return new TableBlock($content->displayOrder(), $rows, $headers, $content->tableWidth());
        }
        if ($content instanceof ImageBlock) {
            return new ImageBlock(
                $content->displayOrder(),
                $content->imageIdentifier(),
                $this->mapOptionalText($content->caption(), $translate),
                $this->mapOptionalText($content->alt(), $translate)
            );
        }
        if ($content instanceof ImageGalleryBlock) {
            return new ImageGalleryBlock($content->displayOrder(), $content->imageIdentifiers(), $this->mapOptionalText($content->caption(), $translate));
        }
        if ($content instanceof EmbedBlock) {
            return new EmbedBlock($content->displayOrder(), $content->provider(), $content->embedId(), $this->mapOptionalText($content->caption(), $translate));
        }
        if ($content instanceof ProfileCardListBlock) {
            return new ProfileCardListBlock($content->displayOrder(), $content->wikiIdentifiers(), $this->mapOptionalText($content->title(), $translate));
        }

        return $content;
    }

    /**
     * @param callable(string, ?int): string $translate
     */
    private function mapOptionalText(?string $text, callable $translate): ?string
    {
        return $text === null ? null : $translate($text, null);
    }

    /**
     * @param array<TableCell>|null $cells
     * @param callable(string, ?int): string $translate
     * @return array<TableCell>|null
     */
    private function mapTableCells(?array $cells, callable $translate): ?array
    {
        return $cells === null ? null : array_map(
            static fn (TableCell $cell): TableCell => $cell->withContent($translate($cell->content(), null)),
            $cells,
        );
    }
}
