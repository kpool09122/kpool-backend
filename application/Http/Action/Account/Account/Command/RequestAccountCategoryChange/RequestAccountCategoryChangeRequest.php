<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\RequestAccountCategoryChange;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Shared\Domain\ValueObject\AccountCategory;

class RequestAccountCategoryChangeRequest extends FormRequest
{
    use ResolvesLanguage {
        language as private actorLanguage;
    }

    public function language(): string
    {
        $acceptLanguage = $this->header('Accept-Language');

        return $acceptLanguage === null ? $this->actorLanguage() : $this->resolveAcceptLanguage($acceptLanguage);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'accountIdentifier' => ['required', 'uuid'],
            'requestedAccountCategory' => ['required', 'string', Rule::in(array_column(AccountCategory::cases(), 'value'))],
        ];
    }

    public function accountIdentifier(): string
    {
        return (string) $this->route('accountIdentifier');
    }

    public function requestedAccountCategory(): string
    {
        return RequestValue::string($this->input('requestedAccountCategory'));
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return [
            ...$this->all(),
            'accountIdentifier' => $this->route('accountIdentifier'),
        ];
    }
}
