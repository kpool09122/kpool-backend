<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\SaveAccountSnapshot;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SaveAccountSnapshotRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['accountIdentifier' => ['required','uuid']];
    }

    /** @return array<array-key,mixed> */
    public function validationData(): array
    {
        return ['accountIdentifier' => $this->route('accountIdentifier')];
    }

    public function accountIdentifier(): string
    {
        return (string)$this->route('accountIdentifier');
    }
}
