<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Webauthn\Counter\CounterChecker;
use Webauthn\CredentialRecord;
use Webauthn\Exception\CounterException;

class PasskeyCounterChecker implements CounterChecker
{
    public function check(CredentialRecord $credentialRecord, int $currentCounter): void
    {
        if ($credentialRecord->backupEligible === true) {
            return;
        }
        if ($credentialRecord->counter > 0 && $currentCounter <= $credentialRecord->counter) {
            throw CounterException::create(
                $credentialRecord->counter,
                $currentCounter,
                'The signature counter did not increase for a single-device credential.',
            );
        }
    }
}
