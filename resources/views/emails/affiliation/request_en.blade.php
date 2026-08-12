<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliation request approval</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="padding: 40px 40px 30px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #333333;">Affiliation request approval</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                You have received an affiliation request.
                            </p>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                Open the affiliation page in the admin console to review the request details and approve it.
                            </p>
                            <div style="text-align: center; margin-bottom: 24px;">
                                <a href="{{ $affiliationUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none; padding: 16px 32px; border-radius: 8px;">
                                    Review Affiliation Request
                                </a>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: #888888; text-align: center;">
                                If you cannot click the button above, please copy and paste the following URL into your browser:<br>
                                <span style="color: #2563eb; word-break: break-all;">{{ $affiliationUrl }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #fafafa; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #999999;">
                                If you did not expect this email, please check with your administrator.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
