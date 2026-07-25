A new inquiry has been received.

Name: {{ (string) $contact->name() }}
Email address: {{ (string) $contact->email() }}
Category: {{ $contact->category()->value }}

{{ (string) $contact->content() }}
