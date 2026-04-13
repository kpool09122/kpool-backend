<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeletePrincipalGroup;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class DeletePrincipalGroupRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function principalGroupId(): string
    {
        return (string) $this->route('principalGroupId');
    }
}
