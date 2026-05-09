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
            'wikiIdentifier' => ['nullable', 'uuid'],
            'status' => ['required', 'string', Rule::in(array_column(ApprovalStatus::cases(), 'value'))],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function wikiIdentifier(): ?string
    {
        $wikiIdentifier = $this->query('wikiIdentifier');

        return $wikiIdentifier === null ? null : (string) $wikiIdentifier;
    }

    public function status(): string
    {
        return (string) $this->query('status');
    }
}
