<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Webauthn\Counter\CounterChecker;
use Webauthn\CredentialRecord;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

class PasskeyCounterChecker implements CounterChecker
{
    public function check(CredentialRecord $credentialRecord, int $currentCounter): void
    {
        if ($credentialRecord->counter === 0 || $currentCounter === 0 || $currentCounter > $credentialRecord->counter) {
            return;
        }
        if ($credentialRecord->backupEligible === true) {
            return;
        }

        throw AuthenticatorResponseVerificationException::create('Authenticator counter did not increase.');
    }
}
