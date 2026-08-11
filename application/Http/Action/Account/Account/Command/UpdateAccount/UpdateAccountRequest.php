<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\UpdateAccount;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountName' => ['required', 'string'],
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

    public function accountId(): string
    {
        return (string) $this->route('accountId');
    }

    public function accountName(): string
    {
        return (string) $this->input('accountName');
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
