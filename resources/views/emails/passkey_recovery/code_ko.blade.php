@include('emails.passkey_recovery.layout', [
    'locale' => 'ko',
    'heading' => '패스키 복구 코드 안내',
    'message' => '패스키 복구를 계속하려면 아래 인증 코드를 입력해 주세요.',
    'detail' => '이 코드는 15분 동안 유효합니다.',
    'notice' => '이 코드를 다른 사람에게 알려주지 마세요. 복구를 요청하지 않으셨다면 즉시 계정을 보호해 주세요.',
])
