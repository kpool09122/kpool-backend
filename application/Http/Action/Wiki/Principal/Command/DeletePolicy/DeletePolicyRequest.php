<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeletePolicy;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class DeletePolicyRequest extends FormRequest
{
    use ResolvesLanguage;

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
}
