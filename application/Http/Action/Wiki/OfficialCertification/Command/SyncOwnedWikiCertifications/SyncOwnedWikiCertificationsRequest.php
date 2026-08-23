<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\SyncOwnedWikiCertifications;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SyncOwnedWikiCertificationsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'translationSetIdentifiers' => ['required', 'array'],
            'translationSetIdentifiers.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    /** @return string[] */
    public function translationSetIdentifiers(): array
    {
        return array_values(array_map('strval', $this->input('translationSetIdentifiers', [])));
    }
}
