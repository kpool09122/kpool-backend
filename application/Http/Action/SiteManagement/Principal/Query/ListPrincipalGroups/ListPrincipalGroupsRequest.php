<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Principal\Query\ListPrincipalGroups;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ListPrincipalGroupsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['accountIdentifier' => ['prohibited']];
    }
}
