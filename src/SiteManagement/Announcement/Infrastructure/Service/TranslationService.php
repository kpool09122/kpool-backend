<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\Service\TranslationServiceInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use UnexpectedValueException;

readonly class TranslationService implements TranslationServiceInterface
{
    public function __construct(private GoogleTranslateClient $googleTranslateClient)
    {
    }

    public function translateAnnouncement(LocalizedAnnouncementContent $source, Language $language): LocalizedAnnouncementContent
    {
        $translations = $this->googleTranslateClient->translateTexts(new TranslateTextsRequest(
            [(string) $source->title, (string) $source->content],
            $language->value,
        ))->translatedTexts();
        if (count($translations) !== 2) {
            throw new UnexpectedValueException('Announcement translation did not return both title and content.');
        }
        $translations = array_values($translations);

        try {
            return new LocalizedAnnouncementContent($language, new Title($translations[0]), new Content($translations[1]));
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedValueException('Announcement translation returned invalid content.', previous: $e);
        }
    }
}
