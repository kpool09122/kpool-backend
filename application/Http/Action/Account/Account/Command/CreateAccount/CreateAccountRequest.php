<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\CreateAccount;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAccountRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'accountType' => ['required', 'string'],
            'accountName' => ['required', 'string'],
            'identityIdentifier' => ['nullable', 'uuid'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'array'],
            'address.countryCode' => ['nullable', 'string', 'size:2'],
            'address.administrativeAreaCode' => [
                'nullable',
                'string',
                'max:16',
                Rule::prohibitedIf(fn (): bool => $this->input('address.countryCode') === null),
            ],
            'address.postalCode' => ['nullable', 'string', 'max:16'],
            'address.locality' => ['nullable', 'string', 'max:64'],
            'address.addressLine1' => ['required_with:address', 'string', 'max:252'],
            'address.addressLine2' => ['nullable', 'string', 'max:252'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function accountType(): string
    {
        return (string) $this->input('accountType');
    }

    public function accountName(): string
    {
        return (string) $this->input('accountName');
    }

    public function identityIdentifier(): ?string
    {
        $value = $this->input('identityIdentifier');

        return $value !== null ? (string) $value : null;
    }

    public function phone(): ?string
    {
        $value = $this->input('phone');

        return $value !== null ? (string) $value : null;
    }

    /** @return array<string, mixed>|null */
    public function address(): ?array
    {
        $value = $this->input('address');

        return is_array($value) ? $value : null;
    }
}
