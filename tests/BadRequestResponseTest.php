<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Yii\Runner\Http\BadRequestResponse;
use Yiisoft\Yii\Runner\Http\Tests\Support\Emitter\NotReadableStream;

final class BadRequestResponseTest extends TestCase
{
    public function testBase(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->assertSame('Test message', $response->getReasonPhrase());
        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
        $this->assertSame([], $response->getHeader('test'));
        $this->assertSame('', $response->getHeaderLine('test'));
        $this->assertSame($response, $response->withoutHeader('test'));
        $this->assertFalse($response->hasHeader('test'));

        $body->close();
        $this->assertNull($body->detach());
        $this->assertNull($body->getSize());
        $this->assertFalse($body->eof());
        $this->assertFalse($body->isSeekable());
        $this->assertFalse($body->isWritable());
        $this->assertFalse($body->isReadable());
        $this->assertNull($body->getMetadata());
    }

    public function testWithProtocolVersion(): void
    {
        $response = new BadRequestResponse('Test message');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "withProtocolVersion" is not supported.');
        $response->withProtocolVersion('1.0');
    }

    public function testWithHeader(): void
    {
        $response = new BadRequestResponse('Test message');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "withHeader" is not supported.');
        $response->withHeader('a', 'b');
    }

    public function testWithAddedHeader(): void
    {
        $response = new BadRequestResponse('Test message');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "withAddedHeader" is not supported.');
        $response->withAddedHeader('a', 'b');
    }

    public function testWithStatus(): void
    {
        $response = new BadRequestResponse('Test message');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "withStatus" is not supported.');
        $response->withStatus(200);
    }

    public function testWithBody(): void
    {
        $response = new BadRequestResponse('Test message');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "withBody" is not supported.');
        $response->withBody(new NotReadableStream());
    }

    public function testBodyGetContents(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "getContents" is not supported.');
        $body->getContents();
    }

    public function testBodyRead(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "read" is not supported.');
        $body->read(7);
    }

    public function testBodyWrite(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "write" is not supported.');
        $body->write('7');
    }

    public function testBodyRewind(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "rewind" is not supported.');
        $body->rewind('7');
    }

    public function testBodySeek(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "seek" is not supported.');
        $body->seek(7);
    }

    public function testBodyTell(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "tell" is not supported.');
        $body->tell();
    }

    public function testBodyToString(): void
    {
        $response = new BadRequestResponse('Test message');
        $body = $response->getBody();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "__toString" is not supported.');
        (string) $body;
    }
}
