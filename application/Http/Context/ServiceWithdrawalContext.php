<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Illuminate\Http\Request;

final class ServiceWithdrawalContext
{
    private const string COMMITTED_ATTRIBUTE = '_identity_withdrawal_committed';

    public static function markCommitted(Request $request): void
    {
        $request->attributes->set(self::COMMITTED_ATTRIBUTE, true);
    }

    public static function isCommitted(Request $request): bool
    {
        return $request->attributes->get(self::COMMITTED_ATTRIBUTE) === true;
    }
}
