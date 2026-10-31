{{ $copy['receivedSubject'] }}

{{ $copy['receivedIntro'] }}

{{ $copy['idLabel'] }}: {{ $contact->contactIdentifier() }}
{{ $copy['categoryLabel'] }}: {{ $copy['category'] }}
{{ $copy['nameLabel'] }}: {!! (string) $contact->name() !!}
{{ $copy['emailLabel'] }}: {!! (string) $contact->email() !!}

{{ $copy['inquiryLabel'] }}:
{!! (string) $contact->content() !!}

{{ $copy['managementNext'] }}
{!! $copy['siteUrl'] !!}

k-pool
