@include('emails.passkey_recovery.layout', [
    'locale' => 'ko',
    'heading' => '패스키 복구가 완료되었습니다',
    'message' => '새 패스키가 등록되었으며 이전 패스키는 모두 삭제되었습니다.',
    'detail' => '기존 로그인 세션은 모두 무효화되었습니다.',
    'notice' => '이 작업을 직접 수행하지 않으셨다면 즉시 계정을 보호해 주세요.',
])
