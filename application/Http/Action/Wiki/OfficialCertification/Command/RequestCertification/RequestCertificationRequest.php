<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
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
            'translationSetIdentifier' => ['required', 'uuid'],
        ];
    }

    public function resourceType(): string
    {
        return RequestValue::string($this->input('resourceType'));
    }

    public function translationSetIdentifier(): string
    {
        return RequestValue::string($this->input('translationSetIdentifier'));
    }
}
