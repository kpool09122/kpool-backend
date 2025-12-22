<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>인증 코드 안내</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Apple SD Gothic Neo', 'Malgun Gothic', 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="padding: 40px 40px 30px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #333333;">인증 코드 안내</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                회원가입을 계속하려면 아래 인증 코드를 입력해 주세요.
                            </p>
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; text-align: center; margin-bottom: 24px;">
                                <span style="font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #2563eb; font-family: 'Courier New', monospace;">{{ (string) $session->authCode() }}</span>
                            </div>
                            <p style="margin: 0 0 8px; font-size: 14px; color: #888888; text-align: center;">
                                이 코드는 <strong style="color: #555555;">15분</strong> 동안 유효합니다.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #fafafa; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #999999;">
                                ※이 이메일을 요청하지 않으셨다면 무시해 주세요. 누군가가 실수로 귀하의 이메일 주소를 입력했을 수 있습니다.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
