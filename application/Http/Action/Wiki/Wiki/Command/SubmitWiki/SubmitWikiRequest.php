<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\SubmitWiki;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SubmitWikiRequest extends FormRequest
{
    use ResolvesLanguage;

    public function isSingle(): bool
    {
        return $this->route('wikiId') !== null;
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        $data = parent::validationData();
        if ($this->isSingle()) {
            $data['submissions'] = [[...$data, 'wikiId' => $this->route('wikiId')]];
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'submissions' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'submissions.*' => ['required', 'array'],
            'submissions.*.wikiId' => ['required', 'uuid', 'distinct:ignore_case'],
            'submissions.*.resourceType' => ['required', 'string', 'in:agency,group,talent,song'],
            'submissions.*.agencyIdentifier' => ['nullable', 'uuid'],
            'submissions.*.groupIdentifiers' => ['nullable', 'array', 'list'],
            'submissions.*.groupIdentifiers.*' => ['uuid'],
            'submissions.*.talentIdentifiers' => ['nullable', 'array', 'list'],
            'submissions.*.talentIdentifiers.*' => ['uuid'],
        ];
    }

    /** @return list<array{wikiId: string, resourceType: string, agencyIdentifier?: ?string, groupIdentifiers?: ?list<string>, talentIdentifiers?: ?list<string>}> */
    public function submissions(): array
    {
        /** @var list<array{wikiId: string, resourceType: string, agencyIdentifier?: ?string, groupIdentifiers?: ?list<string>, talentIdentifiers?: ?list<string>}> $submissions */
        $submissions = $this->validated('submissions');

        return $submissions;
    }
}
