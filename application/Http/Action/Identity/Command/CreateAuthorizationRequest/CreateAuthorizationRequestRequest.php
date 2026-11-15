<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreateAuthorizationRequest;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

class CreateAuthorizationRequestRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'clientIdentifier' => ['required', 'uuid'],
            'redirectUri' => ['required', 'string', 'max:2048'],
            'scopes' => ['required', 'array', 'list', 'min:1', 'max:6'],
            'scopes.*' => ['required', 'string', 'distinct', Rule::enum(AuthorizationScope::class)],
            'resource' => ['required', 'string', 'max:2048'],
            'codeChallenge' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/D'],
            'codeChallengeMethod' => ['required', 'in:S256'],
            'responseType' => ['required', 'in:code'],
            'state' => ['present', 'string', 'max:2048'],
        ];
    }

    public function clientIdentifier(): string
    {
        return RequestValue::string($this->input('clientIdentifier'));
    }

    public function redirectUri(): string
    {
        return RequestValue::string($this->input('redirectUri'));
    }

    public function resource(): string
    {
        return RequestValue::string($this->input('resource'));
    }

    public function codeChallenge(): string
    {
        return RequestValue::string($this->input('codeChallenge'));
    }

    public function state(): string
    {
        return RequestValue::string($this->input('state'));
    }

    /** @return list<string> */
    public function scopes(): array
    {
        /** @var list<string> $values */
        $values = $this->input('scopes');

        return $values;
    }
}
