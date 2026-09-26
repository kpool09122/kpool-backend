@include('emails.passkey_recovery.layout', [
    'locale' => 'en',
    'heading' => 'Your Passkey Recovery Is Complete',
    'message' => 'Your new passkey has been registered, and all previous passkeys have been removed.',
    'detail' => 'All existing login sessions have been signed out.',
    'notice' => 'If you did not perform this action, secure your account immediately.',
])
