k-pool

{!! $copy['intro'] !!}

{!! $copy['wikiLabel'] !!}: {!! $wikiName !!}
@if ($rejectionReason !== null)

{!! $copy['reasonLabel'] !!}:
{!! $rejectionReason !!}
@endif

{!! $copy['next'] !!}

@if ($wikiUrl !== null)
{!! $copy['button'] !!}
{!! $wikiUrl !!}
@endif
