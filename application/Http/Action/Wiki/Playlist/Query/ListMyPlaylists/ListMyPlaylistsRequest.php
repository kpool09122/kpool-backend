<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Query\ListMyPlaylists;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ListMyPlaylistsRequest extends FormRequest
{
    use ResolvesLanguage;
}
