{{ $copy['submitterSubject'] }}

{{ $copy['submitterIntro'] }}

{{ $copy['nameLabel'] }}: {!! (string) $contact->name() !!}
{{ $copy['emailLabel'] }}: {!! (string) $contact->email() !!}

{{ $copy['previousMessageLabel'] }}:
{!! $previousMessage !!}

{{ $copy['replyLabel'] }}:
{!! (string) $replyContent !!}

{{ $copy['managementNext'] }}
{!! $copy['managementUrl'] !!}

k-pool
