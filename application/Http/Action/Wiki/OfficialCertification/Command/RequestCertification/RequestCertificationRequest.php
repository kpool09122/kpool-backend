<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RequestCertificationRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resourceType' => ['required', 'string'],
            'wikiId' => ['required', 'uuid'],
            'ownerAccountId' => ['required', 'uuid'],
        ];
    }

    public function resourceType(): string
    {
        return (string) $this->input('resourceType');
    }

    public function wikiId(): string
    {
        return (string) $this->input('wikiId');
    }

    public function ownerAccountId(): string
    {
        return (string) $this->input('ownerAccountId');
    }
}
