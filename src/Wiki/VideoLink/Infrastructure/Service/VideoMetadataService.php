<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Service;

use CurlHandle;
use GuzzleHttp\Handler\CurlHandler;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;
use Source\Wiki\VideoLink\Application\Service\VideoMetadata;
use Source\Wiki\VideoLink\Application\Service\VideoMetadataServiceInterface;
use Throwable;

final readonly class VideoMetadataService implements VideoMetadataServiceInterface
{
    private const int MAX_PAGE_BYTES = 4_194_304;
    private const int MAX_IMAGE_BYTES = 8_388_608;

    public function __construct(
        private PublicVideoHostResolverInterface $publicVideoHostResolver,
        private ImageServiceInterface $imageService,
        private LoggerInterface $logger,
    ) {
    }

    public function resolve(string $url): VideoMetadata
    {
        $diagnostics = ['stage' => 'page.validate'];

        try {
            return $this->resolveWithDiagnostics($url, $diagnostics);
        } catch (Throwable $e) {
            $this->logger->warning('[debug-log-triage:tiktok-registration] Metadata resolution failed.', [
                ...$diagnostics,
                'exception_class' => $e::class,
            ]);

            throw $e;
        }
    }

    /**
     * @param array<string, scalar|null> $diagnostics
     * @throws VideoMetadataUnavailableException
     * @throws InvalidArgumentException
     */
    private function resolveWithDiagnostics(string $url, array &$diagnostics): VideoMetadata
    {
        $url = trim($url);
        $this->validateUrl($url, false);
        $response = null;
        for ($redirect = 0; $redirect <= 3; ++$redirect) {
            $response = $this->get($url, false, $diagnostics);
            if (! $response->redirect()) {
                break;
            }
            $location = $response->header('Location');
            if (str_starts_with($location, '/') && ! str_starts_with($location, '//')) {
                $location = 'https://' . parse_url($url, PHP_URL_HOST) . $location;
            }
            $diagnostics['stage'] = 'page.redirect_validation';
            $this->validateUrl($location, false);
            $url = $location;
        }
        if (! $response->successful()) {
            throw new VideoMetadataUnavailableException();
        }
        $diagnostics['stage'] = 'page.resolved_url';
        $videoUrl = new TikTokVideoUrl($url);
        if ($videoUrl->externalVideoId() === null) {
            throw new InvalidArgumentException('A resolved TikTok video URL is required.');
        }
        $diagnostics['stage'] = 'page.body';
        $html = $this->body($response, self::MAX_PAGE_BYTES);
        $diagnostics['body_bytes'] = strlen($html);
        $diagnostics['stage'] = 'page.hydration_script';
        if (preg_match('~<script\b[^>]*\bid=["\x27]__UNIVERSAL_DATA_FOR_REHYDRATION__["\x27][^>]*>(.*?)</script\s*>~si', $html, $matches) !== 1) {
            throw new VideoMetadataUnavailableException();
        }

        try {
            $diagnostics['stage'] = 'page.json_decode';
            $data = json_decode($matches[1], true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException) {
            throw new VideoMetadataUnavailableException();
        }
        if (! is_array($data)) {
            throw new VideoMetadataUnavailableException();
        }
        $diagnostics['stage'] = 'page.video_metadata';
        $scope = $data['__DEFAULT_SCOPE__'] ?? null;
        $detail = is_array($scope) ? ($scope['webapp.video-detail'] ?? null) : null;
        $info = is_array($detail) ? ($detail['itemInfo'] ?? null) : null;
        $item = is_array($info) ? ($info['itemStruct'] ?? null) : null;
        $video = is_array($item) ? ($item['video'] ?? null) : null;
        if (! is_array($item) || ($item['id'] ?? null) !== $videoUrl->externalVideoId()
            || ! is_array($video) || ! is_string($video['cover'] ?? null)
            || ! is_int($video['duration'] ?? null) || $video['duration'] < 0 || $video['duration'] > 2147483647) {
            throw new VideoMetadataUnavailableException();
        }

        try {
            $imageUrl = $video['cover'];
            for ($redirect = 0; $redirect <= 3; ++$redirect) {
                $imageResponse = $this->get($imageUrl, true, $diagnostics);
                if (! $imageResponse->redirect()) {
                    break;
                }
                $imageUrl = $imageResponse->header('Location');
            }
            if (! $imageResponse->successful()) {
                throw new VideoMetadataUnavailableException();
            }
            $diagnostics['stage'] = 'image.body';
            $bytes = $this->body($imageResponse, self::MAX_IMAGE_BYTES);
            $diagnostics['body_bytes'] = strlen($bytes);
            $diagnostics['stage'] = 'image.decode';
            $dimensions = @getimagesizefromstring($bytes);
            if ($dimensions === false || ! in_array($dimensions[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)
                || $dimensions[0] * $dimensions[1] > 20_000_000) {
                throw new VideoMetadataUnavailableException();
            }
            $diagnostics['stage'] = 'image.upload';
            $path = $this->imageService->upload(base64_encode($bytes));
        } catch (Throwable $e) {
            $diagnostics['cause_class'] ??= $e::class;

            // Do not propagate signed image URLs or external response bodies to logs.
            throw new VideoMetadataUnavailableException();
        }

        return new VideoMetadata($videoUrl, new DurationSeconds($video['duration']), $path);
    }

    /** @throws InvalidArgumentException */
    private function validateUrl(string $url, bool $image): string
    {
        $parts = parse_url($url);
        if ($parts === false || filter_var($url, FILTER_VALIDATE_URL) === false
            || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new InvalidArgumentException('Unsafe remote video URL.');
        }
        $host = strtolower($parts['host'] ?? '');
        if ($image) {
            foreach (['tiktokcdn.com', 'tiktokcdn-us.com', 'tiktokcdn-eu.com', 'ibytedtos.com', 'byteoversea.com'] as $suffix) {
                if (str_ends_with($host, '.' . $suffix)) {
                    return $host;
                }
            }
        } else {
            new TikTokVideoUrl($url);

            return $host;
        }

        throw new InvalidArgumentException('Unsupported remote video host or path.');
    }

    /**
     * @param array<string, scalar|null> $diagnostics
     * @throws InvalidArgumentException
     * @throws VideoMetadataUnavailableException
     */
    private function get(string $url, bool $image, array &$diagnostics): Response
    {
        $phase = $image ? 'image' : 'page';
        $diagnostics['stage'] = $phase . '.validate';
        unset($diagnostics['http_status'], $diagnostics['content_type'], $diagnostics['body_bytes']);
        $host = $this->validateUrl($url, $image);
        $diagnostics['host'] = $host;
        $diagnostics['stage'] = $phase . '.dns';
        $ip = $this->publicVideoHostResolver->resolve($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE) === false) {
            throw new VideoMetadataUnavailableException();
        }
        $maxBytes = $image ? self::MAX_IMAGE_BYTES : self::MAX_PAGE_BYTES;
        $sink = fopen('php://temp', 'w+');
        if ($sink === false) {
            throw new VideoMetadataUnavailableException();
        }
        $received = 0;

        try {
            $diagnostics['stage'] = $phase . '.request';
            $response = Http::connectTimeout(3)->timeout(8)->setHandler(new CurlHandler())->sink($sink)->withOptions([
                'allow_redirects' => false,
                'proxy' => '',
                'curl' => [
                    CURLOPT_RESOLVE => [$host . ':443:' . $ip],
                    CURLOPT_WRITEFUNCTION => static function (CurlHandle $handle, string $chunk) use ($sink, $maxBytes, &$received): int {
                        $received += strlen($chunk);
                        if ($received > $maxBytes) {
                            return 0;
                        }
                        $written = fwrite($sink, $chunk);

                        return $written === false ? 0 : $written;
                    },
                ],
                'on_headers' => static function (ResponseInterface $response) use ($maxBytes): void {
                    if ((int) $response->getHeaderLine('Content-Length') > $maxBytes) {
                        throw new VideoMetadataUnavailableException();
                    }
                },
            ])->get($url);

            $diagnostics['http_status'] = $response->status();
            $diagnostics['content_type'] = substr($response->header('Content-Type'), 0, 120);
            $diagnostics['stage'] = $phase . '.http_status';

            return $response;
        } catch (Throwable $e) {
            $diagnostics['cause_class'] = $e::class;

            throw new VideoMetadataUnavailableException();
        }
    }

    /** @throws VideoMetadataUnavailableException */
    private function body(Response $response, int $limit): string
    {
        try {
            $stream = $response->toPsrResponse()->getBody();
            $stream->rewind();
            $bytes = '';
            while (! $stream->eof()) {
                $bytes .= $stream->read(min(65536, $limit + 1 - strlen($bytes)));
                if (strlen($bytes) > $limit) {
                    throw new VideoMetadataUnavailableException();
                }
            }

            return $bytes;
        } catch (Throwable) {
            throw new VideoMetadataUnavailableException();
        }
    }
}
