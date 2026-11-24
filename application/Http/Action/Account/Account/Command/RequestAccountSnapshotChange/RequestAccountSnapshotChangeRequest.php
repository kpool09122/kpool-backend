<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\RequestAccountSnapshotChange;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RequestAccountSnapshotChangeRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['accountIdentifier' => ['required','uuid'], 'changeReason' => ['required','string','max:2000']];
    }

    /** @return array<array-key,mixed> */
    public function validationData(): array
    {
        return [...$this->all(), 'accountIdentifier' => $this->route('accountIdentifier')];
    }

    public function changeReason(): string
    {
        return $this->string('changeReason')->toString();
    }

    public function accountIdentifier(): string
    {
        return (string)$this->route('accountIdentifier');
    }
}
