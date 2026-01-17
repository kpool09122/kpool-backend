<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;
use Throwable;

/**
 * RFC 9457 Problem Details for HTTP APIs に準拠したHTTP例外の抽象クラス
 */
abstract class HttpException extends Exception
{
    protected int $httpStatus;
    protected ?string $type = null;
    protected ?string $title = null;
    protected ?string $detail = null;
    protected ?string $instance = null;
    /**
     * @var array<string, mixed>
     */
    protected array $extensions = [];

    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        int $httpStatus,
        ?string $type = null,
        ?string $title = null,
        ?string $detail = null,
        ?string $instance = null,
        array $extensions = [],
        Exception|Throwable|null $previous = null
    ) {
        $this->httpStatus = $httpStatus;
        $this->type = $type;
        $this->title = $title;
        $this->detail = $detail;
        $this->instance = $instance;
        $this->extensions = $extensions;

        // メッセージには detail または title を使用
        $message = $detail ?? $title ?? 'HTTP Exception';

        parent::__construct($message, $httpStatus, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getInstance(): ?string
    {
        return $this->instance;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * RFC 9457準拠の配列表現を取得
     *
     * @return array<string, mixed>
     */
    public function toProblemDetails(): array
    {
        $details = [
            'status' => $this->httpStatus,
        ];

        if ($this->type !== null) {
            $details['type'] = $this->type;
        }

        if ($this->title !== null) {
            $details['title'] = $this->title;
        }

        if ($this->detail !== null) {
            $details['detail'] = $this->detail;
        }

        if ($this->instance !== null) {
            $details['instance'] = $this->instance;
        }

        // 拡張フィールドを追加
        foreach ($this->extensions as $key => $value) {
            $details[$key] = $value;
        }

        return $details;
    }
}
