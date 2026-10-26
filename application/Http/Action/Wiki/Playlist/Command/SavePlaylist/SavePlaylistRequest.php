<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Command\SavePlaylist;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class SavePlaylistRequest extends FormRequest
{
    use ResolvesLanguage;

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $this->merge(['name' => mb_trim($name)]);
        }
        $this->merge(['playlistId' => $this->route('playlistId')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'playlistId' => ['required', 'uuid'],
            'name' => ['required', 'string', 'min:1', 'max:32'],
            'items' => ['present', 'array', 'list', 'max:50'],
            'items.*' => ['required', 'array:provider,externalVideoId,videoLinkIdentifier'],
            'items.*.provider' => ['required_without:items.*.videoLinkIdentifier', 'missing_with:items.*.videoLinkIdentifier', 'string', 'in:youtube'],
            'items.*.externalVideoId' => ['required_without:items.*.videoLinkIdentifier', 'missing_with:items.*.videoLinkIdentifier', 'string', 'regex:/^[a-zA-Z0-9_-]{11}$/D'],
            'items.*.videoLinkIdentifier' => ['required_without_all:items.*.provider,items.*.externalVideoId', 'missing_with:items.*.provider,items.*.externalVideoId', 'uuid'],
        ];
    }

    public function name(): string
    {
        return RequestValue::string($this->input('name'));
    }

    public function playlistId(): string
    {
        return RequestValue::string($this->route('playlistId'));
    }

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        return RequestValue::objects($this->input('items'));
    }
}
