<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountSnapshot;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GetAccountSnapshotRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['accountIdentifier' => ['required','uuid'], 'snapshotIdentifier' => ['required','uuid']];
    }

    /** @return array<array-key,mixed> */
    public function validationData(): array
    {
        return ['accountIdentifier' => $this->route('accountIdentifier'),'snapshotIdentifier' => $this->route('snapshotIdentifier')];
    }

    public function accountIdentifier(): string
    {
        return (string)$this->route('accountIdentifier');
    }

    public function snapshotIdentifier(): string
    {
        return (string)$this->route('snapshotIdentifier');
    }
}
