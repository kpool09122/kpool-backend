{{ $copy['submitterSubject'] }}

{{ $copy['submitterIntro'] }}

{{ $copy['idLabel'] }}: {{ $contact->contactIdentifier() }}
{{ $copy['categoryLabel'] }}: {{ $copy['category'] }}
{{ $copy['nameLabel'] }}: {!! (string) $contact->name() !!}
{{ $copy['emailLabel'] }}: {!! (string) $contact->email() !!}

{{ $copy['replyLabel'] }}:
{!! (string) $replyContent !!}

{{ $copy['managementNext'] }}
{!! $copy['siteUrl'] !!}

k-pool
