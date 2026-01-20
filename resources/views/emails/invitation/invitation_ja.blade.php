<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $accountName }}への招待</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="padding: 40px 40px 30px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #333333;">{{ $accountName }}への招待</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                あなたは {{ $accountName }} のメンバーとして招待されました。
                            </p>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                以下のボタンをクリックして、メンバー登録を完了してください。
                            </p>
                            <div style="text-align: center; margin-bottom: 24px;">
                                <a href="{{ $invitationUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none; padding: 16px 32px; border-radius: 8px;">
                                    メンバー登録へ進む
                                </a>
                            </div>
                            <p style="margin: 0 0 8px; font-size: 14px; color: #888888; text-align: center;">
                                この招待リンクは <strong style="color: #555555;">24時間</strong> 有効です。
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #888888; text-align: center;">
                                上のボタンがクリックできない場合は、以下のURLをブラウザにコピーしてください：<br>
                                <span style="color: #2563eb; word-break: break-all;">{{ $invitationUrl }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #fafafa; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #999999;">
                                ※このメールに心当たりがない場合は、このメールを無視してください。第三者がお客様のメールアドレスを誤って入力した可能性があります。
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
