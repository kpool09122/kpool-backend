<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>소속 요청 승인 요청</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Helvetica Neue', Arial, 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="padding: 40px 40px 30px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #333333;">소속 요청 승인 요청</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                소속 요청이 도착했습니다.
                            </p>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">
                                관리 화면의 소속 페이지를 열어 요청 내용을 확인하고 승인해 주세요.
                            </p>
                            <div style="text-align: center; margin-bottom: 24px;">
                                <a href="{{ $affiliationUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none; padding: 16px 32px; border-radius: 8px;">
                                    소속 요청 확인하기
                                </a>
                            </div>
                            <p style="margin: 0; font-size: 12px; color: #888888; text-align: center;">
                                위 버튼을 클릭할 수 없는 경우, 아래 URL을 브라우저에 복사하여 붙여넣기 해주세요:<br>
                                <span style="color: #2563eb; word-break: break-all;">{{ $affiliationUrl }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px; background-color: #fafafa; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #999999;">
                                ※이 메일에 대해 알지 못하는 경우, 관리자에게 확인해 주세요.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
