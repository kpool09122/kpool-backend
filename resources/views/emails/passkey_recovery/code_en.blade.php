@include('emails.passkey_recovery.layout', [
    'locale' => 'en',
    'heading' => 'Your Passkey Recovery Code',
    'message' => 'Please enter the following verification code to continue recovering your passkeys.',
    'detail' => 'This code is valid for 15 minutes.',
    'notice' => 'Do not share this code with anyone. If you did not request recovery, secure your account immediately.',
])
