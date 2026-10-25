<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Command\CreatePlaylist;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class CreatePlaylistRequest extends FormRequest
{
    use ResolvesLanguage;

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $this->merge(['name' => mb_trim($name)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:1', 'max:32']];
    }

    public function name(): string
    {
        return RequestValue::string($this->input('name'));
    }
}
