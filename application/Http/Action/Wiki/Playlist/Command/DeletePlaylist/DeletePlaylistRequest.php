<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Command\DeletePlaylist;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class DeletePlaylistRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['playlistId' => ['required', 'uuid:7']];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['playlistId' => $this->route('playlistId')]);
    }

    public function playlistId(): string
    {
        return (string) $this->route('playlistId');
    }
}
