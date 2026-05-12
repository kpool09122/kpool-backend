<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListDraftImages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class ListDraftImagesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'translationSetIdentifier' => ['nullable', 'uuid'],
            'status' => ['required', 'string', Rule::in(array_column(ApprovalStatus::cases(), 'value'))],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function translationSetIdentifier(): ?string
    {
        $translationSetIdentifier = $this->query('translationSetIdentifier');

        return $translationSetIdentifier === null ? null : (string) $translationSetIdentifier;
    }

    public function status(): string
    {
        return (string) $this->query('status');
    }
}
