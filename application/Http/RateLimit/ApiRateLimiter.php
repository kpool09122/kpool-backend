<?php

declare(strict_types=1);

namespace Application\Http\RateLimit;

use Application\Http\Exceptions\TooManyRequestsHttpException;
use Illuminate\Cache\RateLimiter;
use InvalidArgumentException;

readonly class ApiRateLimiter
{
    public function __construct(private RateLimiter $rateLimiter)
    {
    }

    public function hit(
        RateLimitSurface $surface,
        RateLimitOperation $operation,
        RateLimitSubject $subject,
    ): void {
        $limits = [
            $this->globalKey($operation) => $this->configuredLimit('global.' . $operation->value),
            $this->subjectKey($surface, $operation, $subject) => $this->configuredLimit(
                sprintf('surfaces.%s.%s.%s', $surface->value, $subject->type, $operation->value),
            ),
        ];

        foreach ($limits as $key => $maximumAttempts) {
            if ($this->rateLimiter->tooManyAttempts($key, $maximumAttempts)) {
                throw new TooManyRequestsHttpException(max(1, $this->rateLimiter->availableIn($key)));
            }
        }

        $decaySeconds = $this->configuredLimit('decay_seconds');
        foreach (array_keys($limits) as $key) {
            $this->rateLimiter->hit($key, $decaySeconds);
        }
    }

    private function globalKey(RateLimitOperation $operation): string
    {
        return 'api-rate-limit:global:' . $operation->value;
    }

    private function subjectKey(
        RateLimitSurface $surface,
        RateLimitOperation $operation,
        RateLimitSubject $subject,
    ): string {
        return sprintf(
            'api-rate-limit:%s:%s:%s:%s',
            $surface->value,
            $subject->type,
            $subject->identifier,
            $operation->value,
        );
    }

    private function configuredLimit(string $path): int
    {
        $value = config('api_rate_limit.' . $path);
        if (! is_int($value) || $value < 1) {
            throw new InvalidArgumentException(sprintf('API rate limit [%s] must be a positive integer.', $path));
        }

        return $value;
    }
}
