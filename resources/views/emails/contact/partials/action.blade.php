<table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 auto 24px;">
    <tr>
        <td align="center" bgcolor="#2563eb" style="border-radius: 8px;">
            <a href="{{ $url }}" style="display: inline-block; padding: 16px 24px; font-size: 16px; line-height: 1.5; font-weight: bold; color: #ffffff; text-decoration: none;">{{ $label }}</a>
        </td>
    </tr>
</table>
@if ($isSiteLink)
    <p style="margin: 0 0 8px; font-size: 12px; line-height: 1.8; color: #777777;">{{ $copy['fallback'] }}</p>
@endif
<p style="margin: 0; font-size: 12px; line-height: 1.8; overflow-wrap: anywhere; word-break: break-all;"><a href="{{ $url }}" style="color: #2563eb;">{{ $address }}</a></p>
