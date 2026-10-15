<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-top: 4px solid #2563eb; border-radius: 8px;">
                    <tr>
                        <td style="padding: 32px 24px 24px; border-bottom: 1px solid #eeeeee;">
                            <p style="margin: 0 0 16px; color: #2563eb; font-size: 16px; font-weight: bold;">k-pool</p>
                            <h1 style="margin: 0; font-size: 24px; line-height: 1.5; color: #222222;">{{ $heading }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 24px;">
                            @yield('body')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px; background-color: #fafafa; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; color: #777777; font-size: 12px; text-align: center;">k-pool</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
