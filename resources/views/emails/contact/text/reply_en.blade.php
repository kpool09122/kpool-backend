{!! (string) $content !!}

@if ($contact->principalIdentifier() !== null)
To view your reply or ask a follow-up question, sign in and open your inquiry history in the dashboard.
@else
For follow-up questions, please reply to this email address: {!! (string) $replyToAddress !!}
@endif

k-pool
