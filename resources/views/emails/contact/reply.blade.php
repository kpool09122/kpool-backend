@extends('emails.layout', ['locale' => $copy['locale'], 'heading' => $copy['replySubject']])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['replyIntro'] }}</p>
    @include('emails.contact.partials.message', ['label' => $copy['previousMessageLabel'], 'message' => $previousMessage])
    @include('emails.contact.partials.message', ['label' => $copy['replyLabel'], 'message' => (string) $content])
    @if ($contact->principalIdentifier() === null)
        <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['anonymousNext'] }}</p>
        <p style="margin: 0; font-size: 16px; line-height: 1.8; color: #555555; overflow-wrap: anywhere;">{{ $replyToAddress }}</p>
    @else
        <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['replyNext'] }}</p>
        @include('emails.contact.partials.action', ['url' => $copy['historyUrl'], 'label' => $copy['openHistory'], 'address' => $copy['historyUrl'], 'isSiteLink' => true])
    @endif
@endsection
