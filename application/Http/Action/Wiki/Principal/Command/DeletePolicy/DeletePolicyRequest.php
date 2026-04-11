<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeletePolicy;

use Illuminate\Foundation\Http\FormRequest;

class DeletePolicyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function policyId(): string
    {
        return (string) $this->route('policyId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
