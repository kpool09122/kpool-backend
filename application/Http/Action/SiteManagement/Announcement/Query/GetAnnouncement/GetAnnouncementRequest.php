<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Query\GetAnnouncement;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GetAnnouncementRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['announcementIdentifier' => ['required', 'uuid:7'], 'language' => ['nullable', 'string', 'in:ja,ko,en']];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['announcementIdentifier' => $this->route('announcementIdentifier')]);
    }

    public function announcementIdentifier(): string
    {
        return (string) $this->route('announcementIdentifier');
    }

    public function contentLanguage(): string
    {
        $language = $this->query('language');

        return $language === null ? $this->language() : (string) $language;
    }
}
