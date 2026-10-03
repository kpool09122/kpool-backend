@include('emails.auth.layout', [
    'locale' => 'en',
    'heading' => 'Confirm SSO Account Linking',
    'message' => 'Please enter the following verification code to link your SSO account to your existing account.',
    'detail' => 'Enter this code within 10 minutes of starting the linking process. Resending the code does not extend this time limit.',
    'notice' => 'Do not share this code with anyone. If you did not request this linking, please ignore this email.',
])
