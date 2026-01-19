<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $accountName }} 초대</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Helvetica Neue', Arial, 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="padding: 40px 40px 30px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #333333;">{{ $accountName }} 초대</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                {{ $accountName }}의 멤버로 초대되었습니다.
                            </p>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                아래 버튼을 클릭하여 멤버 등록을 완료해 주세요.
                            </p>
                            <div style="text-align: center; margin-bottom: 24px;">
                                <a href="{{ $invitationUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none; padding: 16px 32px; border-radius: 8px;">
                                    멤버 등록하기
                                </a>
                            </div>
                            <p style="margin: 0 0 8px; font-size: 14px; color: #888888; text-align: center;">
                                이 초대 링크는 <strong style="color: #555555;">24시간</strong> 동안 유효합니다.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #888888; text-align: center;">
                                위 버튼을 클릭할 수 없는 경우, 아래 URL을 브라우저에 복사하여 붙여넣기 해주세요:<br>
                                <span style="color: #2563eb; word-break: break-all;">{{ $invitationUrl }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #fafafa; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #999999;">
                                ※이 메일에 대해 알지 못하는 경우, 이 메일을 무시해 주세요. 제3자가 실수로 귀하의 이메일 주소를 입력했을 수 있습니다.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
