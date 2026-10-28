{{ $copy['subject'] }}

{{ $copy['intro'] }}

{{ $copy['recentCount'] }}: {{ $recentRequestCount }}
{{ $copy['newCount'] }}: {{ $newRequestCount }}

{{ $copy['featured'] }}
@foreach ($videos as $video)
{{ $video['wikiTitle'] }} — {{ $video['videoTitle'] ?? $copy['deleted'] }}
{{ $copy['likes'] }}: {{ $video['likeCount'] }} / {{ $copy['count'] }}: {{ $video['requestCount'] }}

@endforeach
{{ $copy['button'] }}: {{ $videosUrl }}
