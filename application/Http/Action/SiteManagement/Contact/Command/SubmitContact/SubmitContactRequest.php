<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Command\SubmitContact;

use Illuminate\Foundation\Http\FormRequest;

class SubmitContactRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'integer', 'in:1,2,3,99'],
            'name' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email'],
            'content' => ['required', 'string', 'max:512'],
        ];
    }

    public function category(): int
    {
        return (int)$this->input('category');
    }

    public function name(): string
    {
        return (string)$this->input('name');
    }

    public function email(): string
    {
        return (string)$this->input('email');
    }

    public function content(): string
    {
        return (string)$this->input('content');
    }
}
