<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="table-layout: fixed; margin-bottom: 24px; background-color: #eff6ff; border-radius: 8px;">
    <tr>
        <td style="padding: 20px 24px;">
            @if ($showCategory ?? true)
                <p style="margin: 0; font-size: 14px; line-height: 1.8; color: #555555;">{{ $copy['categoryLabel'] }}: {{ $copy['category'] }}</p>
            @endif
            @if ($showSender)
                <p style="margin: 8px 0 0; font-size: 14px; line-height: 1.8; color: #555555; overflow-wrap: anywhere;">{{ $copy['nameLabel'] }}: {{ $contact->name() }}</p>
                <p style="margin: 0; font-size: 14px; line-height: 1.8; color: #555555; overflow-wrap: anywhere;">{{ $copy['emailLabel'] }}: {{ $contact->email() }}</p>
            @endif
        </td>
    </tr>
</table>
