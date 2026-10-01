<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Action\Identity\Command\WithdrawIdentity\WithdrawIdentityAction;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Log;
use Override;
use Throwable;

class StartApplicationSession extends StartSession
{
    /** @param Request $request */
    #[Override]
    protected function saveSession($request): void
    {
        try {
            parent::saveSession($request);
        } catch (Throwable $exception) {
            if ($request->attributes->get(WithdrawIdentityAction::COMMITTED_ATTRIBUTE) !== true) {
                throw $exception;
            }
            Log::error('Failed to save session after committed identity withdrawal.', ['exception' => $exception]);
        }
    }
}
