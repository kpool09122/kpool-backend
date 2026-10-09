<?php

declare(strict_types=1);

namespace Application\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;

readonly class VisitorLocationVerifier
{
    public function verify(Request $request): VisitorLocation
    {
        $secret = config('wiki.visitor_location_secret');
        if (! is_string($secret) || strlen($secret) < 32) {
            return new VisitorLocation();
        }

        foreach (['X-Kpool-Visitor-Country', 'X-Kpool-Visitor-Region', 'X-Kpool-Visitor-Timestamp', 'X-Kpool-Visitor-Signature', 'Authorization', 'Cookie'] as $header) {
            if (count($request->headers->all($header)) > 1) {
                return new VisitorLocation();
            }
        }

        $timestamp = $request->header('X-Kpool-Visitor-Timestamp', '');
        $signature = $request->header('X-Kpool-Visitor-Signature', '');
        $country = $request->header('X-Kpool-Visitor-Country', '');
        $region = $request->header('X-Kpool-Visitor-Region', '');
        if (! is_string($timestamp) || preg_match('/\A[0-9]{10}\z/', $timestamp) !== 1
            || ! is_string($signature) || preg_match('/\A[a-f0-9]{64}\z/', $signature) !== 1
            || ! is_string($country) || ! is_string($region)
            || strlen($country) > 2 || strlen($region) > 13) {
            return new VisitorLocation();
        }
        $age = Date::now()->getTimestamp() - (int) $timestamp;
        if ($age > 300 || $age < -30) {
            return new VisitorLocation();
        }

        $payload = implode("\n", [
            'kpool-visitor-v1',
            $timestamp,
            strtoupper($request->method()),
            $request->getRequestUri(),
            hash('sha256', $request->getContent()),
            hash('sha256', $request->headers->get('Authorization') ?? ''),
            hash('sha256', $request->headers->get('Cookie') ?? ''),
            $country,
            $region,
        ]);
        if (! hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) {
            return new VisitorLocation();
        }

        try {
            return new VisitorLocation($country === '' ? null : $country, $region === '' ? null : $region);
        } catch (InvalidArgumentException) {
            return new VisitorLocation();
        }
    }
}
