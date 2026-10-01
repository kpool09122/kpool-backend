<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\StartStepUpWithSocial;

use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Identity\Domain\ValueObject\StepUpReturnDestination;

class StartStepUpWithSocialRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['returnTo' => ['required', 'string', Rule::enum(StepUpReturnDestination::class)]];
    }

    public function returnDestination(): string
    {
        return RequestValue::string($this->input('returnTo'));
    }
}
