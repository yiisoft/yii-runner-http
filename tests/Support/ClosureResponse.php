<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests\Support;

use Closure;
use HttpSoft\Message\StreamFactory;
use LogicException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ClosureResponse implements ResponseInterface
{
    private ?StreamInterface $stream = null;

    public function __construct(
        private Closure $body,
    ) {
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        throw new LogicException('Not implemented.');
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function hasHeader(string $name): bool
    {
        return false;
    }

    public function getHeader(string $name): array
    {
        throw new LogicException('Not implemented.');
    }

    public function getHeaderLine(string $name): string
    {
        throw new LogicException('Not implemented.');
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        throw new LogicException('Not implemented.');
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        throw new LogicException('Not implemented.');
    }

    public function withoutHeader(string $name): MessageInterface
    {
        throw new LogicException('Not implemented.');
    }

    public function getBody(): StreamInterface
    {
        return $this->stream ?? $this->stream = (new StreamFactory())->createStream(call_user_func($this->body));
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        throw new LogicException('Not implemented.');
    }

    public function getStatusCode(): int
    {
        return 200;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        throw new LogicException('Not implemented.');
    }

    public function getReasonPhrase(): string
    {
        return 'OK';
    }
}
