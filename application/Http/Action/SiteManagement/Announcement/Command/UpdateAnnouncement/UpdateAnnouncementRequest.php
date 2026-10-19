<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Command\UpdateAnnouncement;

use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'announcementIdentifier' => ['required', 'uuid:7'],
            'language' => ['required', 'string', 'in:ja,ko,en'],
            'category' => ['required', 'integer', 'in:1,2,3'],
            'title' => ['required', 'string', 'max:64'],
            'content' => ['required', 'string', 'max:2048'],
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

    public function language(): string
    {
        return RequestValue::string($this->input('language'));
    }

    public function title(): string
    {
        return RequestValue::string($this->input('title'));
    }

    public function content(): string
    {
        return RequestValue::string($this->input('content'));
    }

    public function category(): int
    {
        return RequestValue::integer($this->input('category'));
    }
}
