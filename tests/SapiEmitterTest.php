<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests;

use HttpSoft\Message\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;
use Yiisoft\Yii\Runner\Http\SapiEmitter;
use Yiisoft\Yii\Runner\Http\Tests\Support\ClosureResponse;
use Yiisoft\Yii\Runner\Http\Tests\Support\Emitter\HTTPFunctions;
use Yiisoft\Yii\Runner\Http\Tests\Support\Emitter\NotReadableStream;
use Yiisoft\Yii\Runner\Http\Tests\Support\Emitter\NotWritableStream;

use function is_string;

final class SapiEmitterTest extends TestCase
{
    public function setUp(): void
    {
        HTTPFunctions::reset();
    }

    public static function tearDownAfterClass(): void
    {
        HTTPFunctions::reset();
    }

    public static function bufferSizeProvider(): array
    {
        return [[null], [1], [100], [1000]];
    }

    /**
     * @dataProvider bufferSizeProvider
     */
    public function testEmit(?int $bufferSize): void
    {
        $body = 'Example body';
        $response = $this->createResponse(Status::OK, ['X-Test' => 1], $body);

        $this
            ->createEmitter($bufferSize)
            ->emit($response);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertCount(2, $this->getHeaders());
        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('Content-Length: ' . strlen($body), $this->getHeaders());
        $this->expectOutputString($body);
    }

    public static function noBodyResponseCodeProvider(): array
    {
        return [[100], [101], [102], [204], [205], [304]];
    }

    /**
     * @dataProvider noBodyResponseCodeProvider
     */
    public function testNoBodyForResponseCode(int $code): void
    {
        $response = $this->createResponse($code, ['X-Test' => 1], 'Example body');

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame($code, $this->getResponseCode());
        $this->assertTrue(HTTPFunctions::hasHeader('X-Test'));
        $this->assertFalse(HTTPFunctions::hasHeader('Content-Length'));
        $this->expectOutputString('');
    }

    public function testEmitterWithNotReadableStream(): void
    {
        $body = new NotReadableStream();
        $response = $this->createResponse(Status::OK, ['X-Test' => 42], $body);

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertCount(1, $this->getHeaders());
        $this->assertContains('X-Test: 42', $this->getHeaders());
    }

    public function testEmitterWithNotWritableStream(): void
    {
        $body = new NotWritableStream();
        $response = $this->createResponse(Status::OK, ['X-Test' => 42], $body);

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertCount(1, $this->getHeaders());
        $this->assertContains('X-Test: 42', $this->getHeaders());
    }

    public function testEmitterWithNotWritableAndNoSeekableStream(): void
    {
        $body = new NotWritableStream(false);
        $response = $this->createResponse(Status::OK, ['X-Test' => 42], $body);

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertCount(1, $this->getHeaders());
        $this->assertContains('X-Test: 42', $this->getHeaders());
    }

    public function testNoBodyAndContentLengthIfEmitToldSo(): void
    {
        $response = $this->createResponse(Status::OK, ['X-Test' => 1], 'Example body');

        $this
            ->createEmitter()
            ->emit($response, true);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertTrue(HTTPFunctions::hasHeader('X-Test'));
        $this->assertFalse(HTTPFunctions::hasHeader('Content-Length'));
        $this->expectOutputString('');
    }

    public function testContentLengthNotOverwrittenIfPresent(): void
    {
        $length = 100;
        $response = $this->createResponse(Status::OK, ['Content-Length' => $length, 'X-Test' => 1], 'Example body');
        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertCount(2, $this->getHeaders());
        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('Content-Length: ' . $length, $this->getHeaders());
        $this->expectOutputString('Example body');
    }

    public function testNoContentLengthHeaderWhenBodyIsEmpty(): void
    {
        $length = 100;
        $response = $this->createResponse(Status::OK, ['Content-Length' => $length, 'X-Test' => 1], '');

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertSame(['X-Test: 1'], $this->getHeaders());
        $this->expectOutputString('');
    }

    public function testContentFullyEmitted(): void
    {
        $body = 'Example body';
        $response = $this->createResponse(Status::OK, ['Content-length' => 1, 'X-Test' => 1], $body);

        $this
            ->createEmitter()
            ->emit($response);

        $this->expectOutputString($body);
    }

    public function testSentHeadersRemoved(): void
    {
        HTTPFunctions::header('Cookie-Set: First Cookie');
        HTTPFunctions::header('Content-Length: 1');
        HTTPFunctions::header('X-Test: 1');

        $body = 'Example body';
        $response = $this->createResponse(Status::OK, [], $body);

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(['Content-Length: ' . strlen($body)], $this->getHeaders());
        $this->expectOutputString($body);
    }

    public function testExceptionWhenBufferSizeLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createEmitter(0);
    }

    public function testExceptionWhenHeadersHaveBeenSent(): void
    {
        $body = 'Example body';
        $response = $this->createResponse(Status::OK, [], $body);
        HTTPFunctions::set_headers_sent(true, 'test-file.php', 200);

        $this->expectException(HeadersHaveBeenSentException::class);
        $this
            ->createEmitter()
            ->emit($response);
    }

    public function testHeadersHaveBeenSentException(): void
    {
        $exception = new HeadersHaveBeenSentException();

        $this->assertSame('HTTP headers have been sent.', $exception->getName());
        $this->assertStringStartsWith('Headers already sent', $exception->getSolution());
        $this->assertStringEndsWith(
            "Emitter can't send headers once the headers block has already been sent.",
            $exception->getSolution(),
        );
    }

    public function testEmitDuplicateHeaders(): void
    {
        $body = 'Example body';
        $response = $this
            ->createResponse(Status::OK, [], $body)
            ->withHeader('X-Test', '1')
            ->withAddedHeader('X-Test', '2')
            ->withAddedHeader('X-Test', '3; 3.5')
            ->withHeader('Cookie-Set', '1')
            ->withAddedHeader('cookie-Set', '2')
            ->withAddedHeader('Cookie-set', '3')
        ;

        (new SapiEmitter())->emit($response);

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('X-Test: 2', $this->getHeaders());
        $this->assertContains('X-Test: 3; 3.5', $this->getHeaders());
        $this->assertContains('Cookie-Set: 1', $this->getHeaders());
        $this->assertContains('Cookie-Set: 2', $this->getHeaders());
        $this->assertContains('Cookie-Set: 3', $this->getHeaders());
        $this->assertContains('Content-Length: ' . strlen($body), $this->getHeaders());
        $this->expectOutputString($body);
    }

    public function testObLevel(): void
    {
        $expectedLevel = ob_get_level();
        $response = $this->createResponse(Status::OK, ['X-Test' => 1]);

        $this
            ->createEmitter()
            ->emit($response);

        $actualLevel = ob_get_level();
        $this->assertSame($expectedLevel, $actualLevel);
    }

    public static function dataExtraObLevel(): iterable
    {
        yield 'empty response' => [
            '',
            1,
        ];
        yield 'some response' => [
            'Example body',
            2,
        ];
    }

    /**
     * @dataProvider dataExtraObLevel
     */
    public function testExtraObLevel(string $responseBody, int $expectedFlushes): void
    {
        $expectedLevel = ob_get_level();
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('read')->willReturnCallback(static function () use ($responseBody) {
            ob_start();
            ob_start();
            ob_start();
            return $responseBody;
        });
        $stream->method('isReadable')->willReturn(true);
        $stream->method('eof')->willReturnOnConsecutiveCalls(false, true);
        $response = $this->createResponse(Status::OK, ['X-Test' => 1])
            ->withBody($stream)
        ;

        $this
            ->createEmitter()
            ->emit($response);

        $actualLevel = ob_get_level();
        $this->assertSame($expectedLevel, $actualLevel);
        $this->assertSame($expectedFlushes, HTTPFunctions::getFlushTimes());
    }

    public function testFlushWithBody(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('read')->willReturnCallback(static fn() => '-');
        $stream->method('isReadable')->willReturn(true);
        $stream->method('eof')->willReturnOnConsecutiveCalls(false, true);
        $response = $this->createResponse(Status::OK, ['X-Test' => 1])
            ->withBody($stream);

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(['X-Test: 1'], HTTPFunctions::getHeader('X-Test'));
        $this->assertSame(2, HTTPFunctions::getFlushTimes());
    }

    public function testFlushWithoutBody(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('eof')->willReturnOnConsecutiveCalls(true);
        $response = $this->createResponse(Status::OK, ['X-Test' => 1])
            ->withBody($stream)
        ;

        $this
            ->createEmitter()
            ->emit($response);

        $this->assertSame(['X-Test: 1'], HTTPFunctions::getHeader('X-Test'));
        $this->assertSame(1, HTTPFunctions::getFlushTimes());
    }

    public function testNotClosedBuffer(): void
    {
        $response1 = new ClosureResponse(static function(){
            return '1';
        });
        $response2 = new ClosureResponse(static function(){
            ob_start();
            return '2';
        });
        $response3 = new ClosureResponse(static function(){
            return '3';
        });

        $emitter = new SapiEmitter();

        $emitter->emit($response1);
        $emitter->emit($response2);
        $emitter->emit($response3);

        $this->assertSame('123', $this->getActualOutput());
    }

    private function createEmitter(?int $bufferSize = null): SapiEmitter
    {
        return new SapiEmitter($bufferSize);
    }

    private function createResponse(
        int $status = Status::OK,
        array $headers = [],
        $body = null
    ): ResponseInterface {
        $response = new Response($status);

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        if ($body instanceof StreamInterface) {
            $response = $response->withBody($body);
        } elseif (is_string($body)) {
            $response
                ->getBody()
                ->write($body);
        }

        return $response;
    }

    private function getHeaders(): array
    {
        return HTTPFunctions::headers_list();
    }

    private function getResponseCode(): int
    {
        return HTTPFunctions::http_response_code();
    }
}
