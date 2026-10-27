<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Query\ListMyVideoWatchRequests;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class ListMyVideoWatchRequestsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'videoLinkIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'videoLinkIds.*' => ['required', 'string', 'uuid'],
        ];
    }

    /** @return list<string> */
    public function videoLinkIds(): array
    {
        return RequestValue::strings($this->validated('videoLinkIds'));
    }
}
