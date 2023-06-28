<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Yiisoft\Http\Status;

/**
 * @internal
 */
final class BadRequestResponse implements ResponseInterface
{
    public function __construct(
        private string $reasonPhrase,
    ) {
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): void
    {
        throw new RuntimeException('Method "withProtocolVersion" is not supported.');
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
        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return '';
    }

    public function withHeader(string $name, $value): self
    {
        throw new RuntimeException('Method "withHeader" is not supported.');
    }

    public function withAddedHeader(string $name, $value): self
    {
        throw new RuntimeException('Method "withAddedHeader" is not supported.');
    }

    public function withoutHeader(string $name): self
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return new class () implements StreamInterface {
            public function __toString(): string
            {
                throw new RuntimeException('Method "__toString" is not supported.');
            }

            public function close(): void
            {
            }

            public function detach()
            {
                return null;
            }

            public function getSize(): ?int
            {
                return null;
            }

            public function tell(): int
            {
                throw new RuntimeException('Method "tell" is not supported.');
            }

            public function eof(): bool
            {
                return false;
            }

            public function isSeekable(): bool
            {
                return false;
            }

            public function seek(int $offset, int $whence = SEEK_SET): void
            {
                throw new RuntimeException('Method "seek" is not supported.');
            }

            public function rewind(): void
            {
                throw new RuntimeException('Method "rewind" is not supported.');
            }

            public function isWritable(): bool
            {
                return false;
            }

            public function write(string $string): void
            {
                throw new RuntimeException('Method "write" is not supported.');
            }

            public function isReadable(): bool
            {
                return false;
            }

            public function read(int $length): void
            {
                throw new RuntimeException('Method "read" is not supported.');
            }

            public function getContents(): void
            {
                throw new RuntimeException('Method "getContents" is not supported.');
            }

            public function getMetadata(?string $key = null): mixed
            {
                return null;
            }
        };
    }

    public function withBody(StreamInterface $body): self
    {
        throw new RuntimeException('Method "withBody" is not supported.');
    }

    public function getStatusCode(): int
    {
        return Status::BAD_REQUEST;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        throw new RuntimeException('Method "withStatus" is not supported.');
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
