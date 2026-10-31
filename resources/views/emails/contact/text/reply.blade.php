{{ $copy['replySubject'] }}

{{ $copy['replyIntro'] }}

{{ $copy['idLabel'] }}: {{ $contact->contactIdentifier() }}
{{ $copy['categoryLabel'] }}: {{ $copy['category'] }}

{{ $copy['replyLabel'] }}:
{!! (string) $content !!}

@if ($contact->principalIdentifier() === null)
{{ $copy['anonymousNext'] }}
{!! (string) $replyToAddress !!}
@else
{{ $copy['replyNext'] }}
{!! $copy['siteUrl'] !!}
@endif

k-pool
