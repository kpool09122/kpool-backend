<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Command\PublishAnnouncement;

use Illuminate\Foundation\Http\FormRequest;

class PublishAnnouncementRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'announcementIdentifier' => ['required', 'uuid:7'],
        ];
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
}
