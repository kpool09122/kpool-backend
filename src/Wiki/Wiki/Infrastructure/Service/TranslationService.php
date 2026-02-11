<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Application\Service\TranslatedWikiData;
use Source\Wiki\Wiki\Application\Service\TranslationServiceInterface;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\QuoteBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentInterface;

readonly class TranslationService implements TranslationServiceInterface
{
    public function __construct(
        private GoogleTranslateClient $googleTranslateClient,
    ) {
    }

    public function translateWiki(Wiki $wiki, Language $targetLanguage): TranslatedWikiData
    {
        $basicArray = $wiki->basic()->toArray();
        $basicType = $wiki->basic()->getBasicType();
        $translatableKeys = match ($basicType) {
            'talent' => ['name', 'real_name'],
            'agency' => ['name', 'ceo'],
            'group' => ['name'],
            'song' => ['name', 'lyricist', 'composer', 'arranger'],
            default => throw new InvalidArgumentException("Unsupported basic type: $basicType"),
        };

        // 翻訳対象のテキストを収集
        $texts = [];
        foreach ($translatableKeys as $key) {
            $texts[] = $basicArray[$key] ?? '';
        }
        $this->collectSectionTexts($wiki->sections(), $texts);

        // 一括翻訳
        $request = new TranslateTextsRequest(
            texts: $texts,
            targetLanguage: $targetLanguage->value,
        );
        $response = $this->googleTranslateClient->translateTexts($request);
        $translations = $response->translatedTexts();

        // Basic情報を再構築
        $offset = 0;
        foreach ($translatableKeys as $key) {
            $basicArray[$key] = $translations[$offset] ?? $basicArray[$key];
            $offset++;
        }
        $translatedBasic = match ($basicArray['type']) {
            'talent' => TalentBasic::fromArray($basicArray),
            'agency' => AgencyBasic::fromArray($basicArray),
            'group' => GroupBasic::fromArray($basicArray),
            'song' => SongBasic::fromArray($basicArray),
            default => throw new InvalidArgumentException("Unsupported basic type: {$basicArray['type']}"),
        };

        // セクションを再構築
        $translatedSections = $this->reconstructSections($wiki->sections(), $translations, $offset);

        return new TranslatedWikiData($translatedBasic, $translatedSections);
    }

    /**
     * @param string[] &$texts
     */
    private function collectSectionTexts(SectionContentCollection $sections, array &$texts): void
    {
        foreach ($sections->all() as $content) {
            if ($content instanceof Section) {
                $texts[] = $content->title();
                $this->collectSectionTexts($content->contents(), $texts);
            } elseif ($content instanceof TextBlock) {
                $texts[] = $content->content();
            } elseif ($content instanceof QuoteBlock) {
                $texts[] = $content->content();
                if ($content->source() !== null) {
                    $texts[] = $content->source();
                }
            } elseif ($content instanceof ListBlock) {
                foreach ($content->items() as $item) {
                    $texts[] = $item;
                }
            } elseif ($content instanceof TableBlock) {
                if ($content->headers() !== null) {
                    foreach ($content->headers() as $header) {
                        $texts[] = $header;
                    }
                }
                foreach ($content->rows() as $row) {
                    foreach ($row as $cell) {
                        $texts[] = $cell;
                    }
                }
            }
        }
    }

    /**
     * @param string[] $translations
     */
    private function reconstructSections(
        SectionContentCollection $sections,
        array $translations,
        int &$offset,
    ): SectionContentCollection {
        $translatedContents = [];
        foreach ($sections->all() as $content) {
            $translatedContents[] = $this->reconstructContent($content, $translations, $offset);
        }

        return new SectionContentCollection($translatedContents);
    }

    /**
     * @param string[] $translations
     */
    private function reconstructContent(
        SectionContentInterface $content,
        array $translations,
        int &$offset,
    ): SectionContentInterface {
        if ($content instanceof Section) {
            $translatedTitle = $translations[$offset] ?? $content->title();
            $offset++;
            $translatedContents = $this->reconstructSections($content->contents(), $translations, $offset);

            return new Section($translatedTitle, $content->displayOrder(), $translatedContents, $content->depth());
        }

        if ($content instanceof TextBlock) {
            $translatedText = $translations[$offset] ?? $content->content();
            $offset++;

            return new TextBlock($content->displayOrder(), $translatedText);
        }

        if ($content instanceof QuoteBlock) {
            $translatedText = $translations[$offset] ?? $content->content();
            $offset++;
            $translatedSource = null;
            if ($content->source() !== null) {
                $translatedSource = $translations[$offset] ?? $content->source();
                $offset++;
            }

            return new QuoteBlock($content->displayOrder(), $translatedText, $translatedSource);
        }

        if ($content instanceof ListBlock) {
            $translatedItems = [];
            foreach ($content->items() as $item) {
                $translatedItems[] = $translations[$offset] ?? $item;
                $offset++;
            }

            return new ListBlock($content->displayOrder(), $content->listType(), $translatedItems);
        }

        if ($content instanceof TableBlock) {
            $translatedHeaders = null;
            if ($content->headers() !== null) {
                $translatedHeaders = [];
                foreach ($content->headers() as $header) {
                    $translatedHeaders[] = $translations[$offset] ?? $header;
                    $offset++;
                }
            }
            $translatedRows = [];
            foreach ($content->rows() as $row) {
                $translatedRow = [];
                foreach ($row as $cell) {
                    $translatedRow[] = $translations[$offset] ?? $cell;
                    $offset++;
                }
                $translatedRows[] = $translatedRow;
            }

            return new TableBlock($content->displayOrder(), $translatedRows, $translatedHeaders);
        }

        // 翻訳不要なブロック（ImageBlock, EmbedBlock等）はそのまま返す
        return $content;
    }
}
