<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListManagedImages;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class ListManagedImagesRequest extends FormRequest
{
    use ResolvesLanguage;

    protected function prepareForValidation(): void
    {
        foreach (['authorMissing', 'licenseMissing'] as $key) {
            if (in_array($this->input($key), ['true', 'false'], true)) {
                $this->merge([$key => $this->input($key) === 'true']);
            }
        }
    }

    public function license(): ?string
    {
        return $this->filled('license') ? $this->string('license')->toString() : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'license' => ['nullable', Rule::enum(ImageLicense::class)],
            'authorMissing' => ['nullable', 'boolean'],
            'licenseMissing' => ['nullable', 'boolean'],
            'status' => ['required', Rule::enum(ApprovalStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }
}
