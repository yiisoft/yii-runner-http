<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests\Support\Emitter;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class NotWritableEmptyStream implements StreamInterface
{
    public function __toString(): string
    {
        return '';
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
        return 1;
    }

    public function eof(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
    }

    public function rewind(): void
    {
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new RuntimeException();
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        return '';
    }

    public function getContents(): string
    {
        return '';
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}
