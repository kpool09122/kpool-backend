@extends('emails.contact.layout', ['locale' => 'en', 'heading' => 'A New Inquiry Has Been Received'])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">A new inquiry has been received.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px; font-size: 14px; line-height: 1.6;">
        <tr>
            <td width="120" valign="top" style="padding: 8px 12px 8px 0; color: #777777;">Name</td>
            <td style="padding: 8px 0; color: #333333; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $contact->name() }}</td>
        </tr>
        <tr>
            <td width="120" valign="top" style="padding: 8px 12px 8px 0; color: #777777;">Email address</td>
            <td style="padding: 8px 0; color: #333333; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $contact->email() }}</td>
        </tr>
        <tr>
            <td width="120" valign="top" style="padding: 8px 12px 8px 0; color: #777777;">Category</td>
            <td style="padding: 8px 0; color: #333333; overflow-wrap: anywhere; word-break: break-word;">{{ $categoryLabel }}</td>
        </tr>
    </table>
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $contact->content() }}</div>
@endsection
