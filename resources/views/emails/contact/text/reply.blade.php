{{ $copy['replySubject'] }}

{{ $copy['replyIntro'] }}


{{ $copy['previousMessageLabel'] }}:
{!! $previousMessage !!}

{{ $copy['replyLabel'] }}:
{!! (string) $content !!}

@if ($contact->principalIdentifier() === null)
{{ $copy['anonymousNext'] }}
{!! (string) $replyToAddress !!}
@else
{{ $copy['replyNext'] }}
{!! $copy['historyUrl'] !!}
@endif

k-pool
