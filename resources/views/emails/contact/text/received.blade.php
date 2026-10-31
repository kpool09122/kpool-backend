{{ $copy['receivedSubject'] }}

{{ $copy['receivedIntro'] }}

{{ $copy['categoryLabel'] }}: {{ $copy['category'] }}
{{ $copy['nameLabel'] }}: {!! (string) $contact->name() !!}
{{ $copy['emailLabel'] }}: {!! (string) $contact->email() !!}

{{ $copy['inquiryLabel'] }}:
{!! (string) $contact->content() !!}

{{ $copy['managementNext'] }}
{!! $copy['managementUrl'] !!}

k-pool
