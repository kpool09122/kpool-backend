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
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
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
        private WikiRepositoryInterface $wikiRepository,
        private WikiSnapshotRepositoryInterface $wikiSnapshotRepository,
    ) {
    }

    public function translateWiki(Wiki $wiki, Language $targetLanguage): TranslatedWikiData
    {
        $existing = $this->wikiRepository->findByTranslationSetIdentifierAndLanguages($wiki->translationSetIdentifier(), [$targetLanguage])[0] ?? null;
        $baseline = $this->comparisonSource($wiki, $existing);
        $texts = [];
        $this->mapWikiTexts($wiki, $targetLanguage, static function (string $text, ?int $maxLength, ?string $reused = null) use (&$texts): string {
            if ($reused !== null) {
                return $reused;
            }
            if (trim($text) !== '') {
                $texts[] = $text;
            }

            return $text;
        }, $baseline, $existing);

        if ($texts === []) {
            return $this->mapWikiTexts($wiki, $targetLanguage, static fn (string $text, ?int $maxLength, ?string $reused = null): string => $reused ?? $text, $baseline, $existing);
        }

        $translations = $this->googleTranslateClient->translateTexts(new TranslateTextsRequest(
            texts: $texts,
            targetLanguage: $targetLanguage->value,
        ))->translatedTexts();
        $offset = 0;

        return $this->mapWikiTexts($wiki, $targetLanguage, static function (string $text, ?int $maxLength, ?string $reused = null) use ($translations, &$offset): string {
            if ($reused !== null) {
                return $reused;
            }
            if (trim($text) === '') {
                return $text;
            }

            $translated = $translations[$offset++] ?? $text;
            if (trim($translated) === '') {
                return $text;
            }

            return $maxLength === null ? $translated : mb_substr(trim($translated), 0, $maxLength);
        }, $baseline, $existing);
    }

    /**
     * A section's own text is one scope; nested sections have independent scopes.
     * @return list<SectionContentInterface>
     */
    private function scopeParts(SectionContentInterface $content): array
    {
        return $content instanceof Section
            ? array_values(array_filter($content->contents()->sorted(), static fn (SectionContentInterface $child): bool => ! $child instanceof Section))
            : [$content];
    }

    /** @return list<string> */
    private function scopeTexts(SectionContentInterface $content): array
    {
        $texts = $content instanceof Section ? [$content->title()] : [];
        foreach ($this->scopeParts($content) as $part) {
            $this->mapContent($part, Language::KOREAN, static function (string $text, ?int $maxLength) use (&$texts): string {
                $texts[] = $text;

                return $text;
            });
        }

        return $texts;
    }

    private function scopeShape(SectionContentInterface $content): string
    {
        $shape = [$content::class];
        foreach ($this->scopeParts($content) as $part) {
            $shape[] = [$part::class, count($this->scopeTexts($part)), match (true) {
                $part instanceof ImageBlock => [$part->caption() !== null, $part->alt() !== null],
                $part instanceof QuoteBlock => [$part->source() !== null],
                $part instanceof TableBlock => [count($part->headerCells() ?? []), array_map(count(...), $part->rowCells())],
                default => [],
            }];
        }

        return serialize($shape);
    }

    private function scopeKey(SectionContentInterface $content): string
    {
        return SectionTranslationFingerprint::of($content);
    }

    /**
     * Match snapshot scopes to translations only when their complete subtree shape
     * is unique among siblings on both sides. Position and translated titles are
     * not identity; indistinguishable siblings must be translated again.
     * @param array<string, list<SectionContentInterface>> $reuse
     */
    private function indexSectionTranslations(SectionContentCollection $baseline, SectionContentCollection $existing, array &$reuse): void
    {
        $sources = [];
        $targets = [];
        foreach ($baseline->all() as $content) {
            $sources[$this->treeShape($content)][] = $content;
        }
        foreach ($existing->all() as $content) {
            $targets[$this->treeShape($content)][] = $content;
        }
        foreach ($sources as $shape => $candidates) {
            $translations = $targets[$shape] ?? [];
            if (count($candidates) !== 1 || count($translations) !== 1) {
                continue;
            }
            $source = $candidates[0];
            $target = $translations[0];
            $reuse[$this->scopeKey($source)][] = $target;
            if ($source instanceof Section && $target instanceof Section) {
                $this->indexSectionTranslations($source->contents(), $target->contents(), $reuse);
            }
        }
    }

    private function treeShape(SectionContentInterface $content): string
    {
        $children = [];
        if ($content instanceof Section) {
            foreach ($content->contents()->all() as $child) {
                if ($child instanceof Section) {
                    $children[] = $this->treeShape($child);
                }
            }
            sort($children);
        }

        return serialize([$this->scopeShape($content), $children]);
    }

    /** @param array<string, int> $counts */
    private function countSectionScopes(SectionContentCollection $sections, array &$counts): void
    {
        foreach ($sections->all() as $content) {
            $key = $this->scopeKey($content);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            if ($content instanceof Section) {
                $this->countSectionScopes($content->contents(), $counts);
            }
        }
    }

    /**
     * @param callable(string, ?int, ?string=): string $translate
     * @param array<string, list<SectionContentInterface>> $reuse
     * @param array<string, int> $counts
     */
    private function mapDifferentialSections(SectionContentCollection $sections, Language $targetLanguage, callable $translate, array $reuse, array $counts): SectionContentCollection
    {
        $mapped = [];
        foreach ($sections->all() as $content) {
            $key = $this->scopeKey($content);
            $candidates = $reuse[$key] ?? [];
            $texts = count($candidates) === 1 && $counts[$key] === 1 && $this->scopeShape($content) === $this->scopeShape($candidates[0]) ? $this->scopeTexts($candidates[0]) : [];
            $offset = 0;
            $scopeTranslate = static function (string $text, ?int $maxLength) use ($translate, $texts, &$offset): string {
                $reused = $texts[$offset++] ?? null;

                return $translate($text, $maxLength, $reused !== null && trim($reused) !== '' ? $reused : null);
            };
            if ($content instanceof Section) {
                $title = $scopeTranslate($content->title(), null);
                $children = [];
                foreach ($content->contents()->sorted() as $child) {
                    if ($child instanceof Section) {
                        $children[] = $this->mapDifferentialSections(new SectionContentCollection([$child]), $targetLanguage, $translate, $reuse, $counts)->all()[0];
                    } else {
                        $children[] = $this->mapContent($child, $targetLanguage, $scopeTranslate);
                    }
                }
                $mapped[] = new Section(
                    $targetLanguage === Language::ENGLISH && trim($texts[0] ?? '') === '' ? ucfirst($title) : $title,
                    $content->displayOrder(),
                    new SectionContentCollection($children),
                    $content->depth(),
                );

            } else {
                $mapped[] = $this->mapContent($content, $targetLanguage, $scopeTranslate);
            }
        }

        return new SectionContentCollection($mapped);
    }

    private function reusableText(?string $current, ?string $previous, ?string $translated): ?string
    {
        return $current !== null && $current === $previous && $translated !== null && trim($translated) !== '' ? $translated : null;
    }

    private function comparisonSource(Wiki $wiki, ?Wiki $existing): Wiki|WikiSnapshot|null
    {
        if ($existing === null || $existing->version()->value() > $wiki->version()->value()) {
            return null;
        }
        $latest = $this->wikiSnapshotRepository->findLatestByWikiIdentifier($wiki->wikiIdentifier());
        if ($latest === null) {
            return $existing->hasSameVersion($wiki->version()) ? $wiki : null;
        }

        // Multiple revisions share a version but no source-revision identity is stored
        // on translations. Reuse would risk retaining text from an older revision.
        if (count($this->wikiSnapshotRepository->findAllByWikiAndVersion($wiki->wikiIdentifier(), $latest->version())) > 1) {
            return null;
        }

        // A lagging translation cannot be paired with a newer source snapshot.
        return $existing->hasSameVersion($latest->version()) ? $latest : null;
    }

    /**
     * @param callable(string, ?int, ?string=): string $translate
     */
    private function mapWikiTexts(Wiki $wiki, Language $targetLanguage, callable $translate, Wiki|WikiSnapshot|null $baseline = null, ?Wiki $existing = null): TranslatedWikiData
    {
        $basic = $this->mapBasic($wiki->basic(), $translate, $baseline?->basic(), $existing?->basic());
        $reuse = [];
        if ($baseline !== null && $existing !== null) {
            $this->indexSectionTranslations($baseline->sections(), $existing->sections(), $reuse);
            $baselineCounts = [];
            $this->countSectionScopes($baseline->sections(), $baselineCounts);
            foreach ($reuse as $key => $candidates) {
                if (($baselineCounts[$key] ?? 0) !== 1) {
                    unset($reuse[$key]);
                }
            }
        }
        $counts = [];
        $this->countSectionScopes($wiki->sections(), $counts);
        $sections = $this->mapDifferentialSections($wiki->sections(), $targetLanguage, $translate, $reuse, $counts);
        $title = $wiki->title();
        $description = $wiki->metaDescription();
        $keywords = $wiki->keywords();

        return new TranslatedWikiData(
            translatedBasic: $basic,
            translatedSections: $sections,
            translatedTitle: $title === null ? null : new SeoTitle($translate((string) $title, SeoTitle::MAX_LENGTH, $this->reusableText((string) $title, $baseline?->title() === null ? null : (string) $baseline->title(), $existing?->title() === null ? null : (string) $existing->title()))),
            translatedMetaDescription: $description === null ? null : new MetaDescription($translate((string) $description, MetaDescription::MAX_LENGTH, $this->reusableText((string) $description, $baseline?->metaDescription() === null ? null : (string) $baseline->metaDescription(), $existing?->metaDescription() === null ? null : (string) $existing->metaDescription()))),
            translatedKeywords: $keywords === null ? null : ($baseline?->keywords()?->values() === $keywords->values() && $existing?->keywords() !== null && count($existing->keywords()->values()) === count($keywords->values())
                ? $existing->keywords()
                : new SeoKeywords(array_map(
                    static fn (string $keyword): string => $translate($keyword, SeoKeywords::MAX_KEYWORD_LENGTH),
                    $keywords->values(),
                ))),
        );
    }

    /**
     * @param callable(string, ?int, ?string=): string $translate
     */
    private function mapBasic(BasicInterface $basic, callable $translate, ?BasicInterface $baseline = null, ?BasicInterface $existing = null): BasicInterface
    {
        $data = $basic->toArray();
        $previous = $baseline?->getBasicType() === $basic->getBasicType() ? $baseline->toArray() : [];
        $translated = $existing?->getBasicType() === $basic->getBasicType() ? $existing->toArray() : [];
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
                $data[$key] = $translate(TypedValue::string($data[$key]), $maxLength, $this->reusableText(TypedValue::string($data[$key]), TypedValue::nullableString($previous[$key] ?? null), TypedValue::nullableString($translated[$key] ?? null)));
            }
        }
        if ($basic instanceof GroupBasic) {
            $previousColors = $baseline instanceof GroupBasic ? $baseline->officialColors() : [];
            $translatedColors = $existing instanceof GroupBasic ? $existing->officialColors() : [];
            $data['official_colors'] = array_map(function (mixed $color) use ($translate, $previousColors, $translatedColors): array {
                $colorData = TypedValue::array($color);
                $code = TypedValue::string($colorData['color_code']);
                $previousMatches = array_values(array_filter($previousColors, static fn (Color $candidate): bool => (string) $candidate->colorCode() === $code));
                $translatedMatches = array_values(array_filter($translatedColors, static fn (Color $candidate): bool => (string) $candidate->colorCode() === $code));
                $reused = count($previousMatches) === 1 && count($translatedMatches) === 1
                    ? $this->reusableText(TypedValue::string($colorData['label']), $previousMatches[0]->label(), $translatedMatches[0]->label())
                    : null;
                $colorData['label'] = $translate(TypedValue::string($colorData['label']), Color::MAX_LABEL_LENGTH, $reused);

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
     * @param callable(string, ?int, ?string=): string $translate
     */
    private function mapSections(SectionContentCollection $sections, Language $targetLanguage, callable $translate): SectionContentCollection
    {
        return new SectionContentCollection(array_map(
            fn (SectionContentInterface $content): SectionContentInterface => $this->mapContent($content, $targetLanguage, $translate),
            $sections->all(),
        ));
    }

    /**
     * @param callable(string, ?int, ?string=): string $translate
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
     * @param callable(string, ?int, ?string=): string $translate
     */
    private function mapOptionalText(?string $text, callable $translate): ?string
    {
        return $text === null ? null : $translate($text, null);
    }

    /**
     * @param array<TableCell>|null $cells
     * @param callable(string, ?int, ?string=): string $translate
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
