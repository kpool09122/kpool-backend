<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Query\ListMyVideoLinkLikes;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class ListMyVideoLinkLikesRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'videoLinkIds' => ['sometimes', 'required', 'array', 'list', 'min:1', 'max:100'],
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'videoLinkIds.*' => ['required', 'string', 'uuid'],
        ];
    }

    /** @return list<string>|null */
    public function videoLinkIds(): ?array
    {
        $ids = $this->validated('videoLinkIds');

        return $ids === null ? null : RequestValue::strings($ids);
    }

    public function page(): int
    {
        return $this->integer('page', 1);
    }

    public function perPage(): int
    {
        return $this->integer('perPage', 20);
    }
}
