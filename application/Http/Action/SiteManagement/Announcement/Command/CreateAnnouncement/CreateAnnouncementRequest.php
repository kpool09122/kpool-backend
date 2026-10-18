<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Command\CreateAnnouncement;

use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class CreateAnnouncementRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'in:ja,ko,en'],
            'category' => ['required', 'integer', 'in:1,2,3'],
            'title' => ['required', 'string', 'max:64'],
            'content' => ['required', 'string', 'max:2048'],
            'publishedDate' => ['required', 'date_format:Y-m-d'],
        ];
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

    public function publishedDate(): string
    {
        return RequestValue::string($this->input('publishedDate'));
    }

    public function category(): int
    {
        return RequestValue::integer($this->input('category'));
    }
}
