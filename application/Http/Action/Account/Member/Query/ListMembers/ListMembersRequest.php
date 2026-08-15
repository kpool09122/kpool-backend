<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Member\Query\ListMembers;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ListMembersRequest extends FormRequest
{
    use ResolvesLanguage;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
