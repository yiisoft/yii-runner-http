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
    /**
     * @var array[]
     */
    private array $headers;

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
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        throw new LogicException('Not implemented.');
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $new = clone $this;
        $new->headers[$name] = (array) $value;
        return $new;
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
        return $this->stream ?? $this->stream = (new StreamFactory())->createStream(($this->body)());
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
