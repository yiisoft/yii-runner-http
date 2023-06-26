<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests;

use HttpSoft\Message\ServerRequestFactory as PsrServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\UriFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Yiisoft\Yii\Runner\Http\Exception\BadRequestException;
use Yiisoft\Yii\Runner\Http\ServerRequestFactory;

use function fopen;
use function fwrite;
use function rewind;

final class ServerRequestFactoryTest extends TestCase
{
    public function testUploadedFiles(): void
    {
        $_SERVER = [
            'HTTP_HOST' => 'test',
            'REQUEST_METHOD' => 'GET',
        ];
        $_FILES = [
            'file1' => [
                'name' => $firstFileName = 'facepalm.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/123',
                'error' => '0',
                'size' => '31059',
            ],
            'file2' => [
                'name' => [$secondFileName = 'facepalm2.jpg', $thirdFileName = 'facepalm3.jpg'],
                'type' => ['image/jpeg', 'image/jpeg'],
                'tmp_name' => ['/tmp/phpJutmOS', '/tmp/php9bNI8F'],
                'error' => ['0', '0'],
                'size' => ['78085', '61429'],
            ],
        ];

        $serverRequest = $this
            ->createServerRequestFactory()
            ->createFromGlobals();

        $firstUploadedFile = $serverRequest->getUploadedFiles()['file1'];
        $this->assertSame($firstFileName, $firstUploadedFile->getClientFilename());

        $secondUploadedFile = $serverRequest->getUploadedFiles()['file2'][0];
        $this->assertSame($secondFileName, $secondUploadedFile->getClientFilename());

        $thirdUploadedFile = $serverRequest->getUploadedFiles()['file2'][1];
        $this->assertSame($thirdFileName, $thirdUploadedFile->getClientFilename());
    }

    public function testHeadersParsing(): void
    {
        $_SERVER = [
            'HTTP_HOST' => 'example.com',
            'CONTENT_TYPE' => 'text/plain',
            'REQUEST_METHOD' => 'GET',
            'REDIRECT_STATUS' => '200',
            'REDIRECT_HTTP_HOST' => 'example.org',
            'REDIRECT_HTTP_CONNECTION' => 'keep-alive',
        ];

        $expected = [
            'Host' => ['example.com'],
            'Content-Type' => ['text/plain'],
            'Connection' => ['keep-alive'],
        ];

        $serverRequest = $this
            ->createServerRequestFactory()
            ->createFromGlobals();

        $this->assertSame($expected, $serverRequest->getHeaders());
    }

    public function testInvalidMethodException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to determine HTTP request method.');

        $this
            ->createServerRequestFactory()
            ->createFromParameters([]);
    }

    public function bodyDataProvider(): array
    {
        $content = 'content';
        $resource = fopen('php://memory', 'wb+');
        fwrite($resource, $content);
        rewind($resource);

        return [
            'StreamFactoryInterface' => [(new StreamFactory())->createStream('content'), $content],
            'resource' => [$resource, $content],
            'string' => [$content, $content],
            'null' => [null, ''],
        ];
    }

    /**
     * @dataProvider bodyDataProvider
     */
    public function testBody(mixed $body, string $expected): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];
        $request = $this
            ->createServerRequestFactory()
            ->createFromParameters($server, [], [], [], [], [], $body);

        $this->assertSame($expected, (string) $request->getBody());
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'int' => [1],
            'float' => [1.1],
            'true' => [true],
            'false' => [false],
            'empty-array' => [[]],
            'object' => [new StdClass()],
            'callable' => [static fn () => null],
        ];
    }

    /**
     * @dataProvider invalidBodyDataProvider
     */
    public function testInvalidBodyException(mixed $body): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Body parameter for "ServerRequestFactory::createFromParameters()" must be instance of StreamInterface, resource or null.',
        );

        $this
            ->createServerRequestFactory()
            ->createFromParameters($server, [], [], [], [], [], $body);
    }

    public function hostParsingDataProvider(): array
    {
        return [
            'host' => [
                [
                    'HTTP_HOST' => 'test',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'hostWithPort' => [
                [
                    'HTTP_HOST' => 'test:88',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => 88,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'ipv4' => [
                [
                    'HTTP_HOST' => '127.0.0.1',
                    'REQUEST_METHOD' => 'GET',
                    'HTTPS' => true,
                ],
                [
                    'method' => 'GET',
                    'host' => '127.0.0.1',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'https',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'ipv4WithPort' => [
                [
                    'HTTP_HOST' => '127.0.0.1:443',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => '127.0.0.1',
                    'port' => 443,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'ipv6' => [
                [
                    'HTTP_HOST' => '[::1]',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => '[::1]',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'ipv6WithPort' => [
                [
                    'HTTP_HOST' => '[::1]:443',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => '[::1]',
                    'port' => 443,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'serverName' => [
                [
                    'SERVER_NAME' => 'test',
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'hostAndServerName' => [
                [
                    'SERVER_NAME' => 'override',
                    'HTTP_HOST' => 'test',
                    'REQUEST_METHOD' => 'GET',
                    'SERVER_PORT' => 81,
                ],
                [
                    'method' => 'GET',
                    'host' => 'test',
                    'port' => 81,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'none' => [
                [
                    'REQUEST_METHOD' => 'GET',
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'path' => [
                [
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => '/path/to/folder?param=1',
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '/path/to/folder',
                    'query' => '',
                ],
            ],
            'query' => [
                [
                    'REQUEST_METHOD' => 'GET',
                    'QUERY_STRING' => 'path/to/folder?param=1',
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => 'path/to/folder?param=1',
                ],
            ],
            'protocol' => [
                [
                    'REQUEST_METHOD' => 'GET',
                    'SERVER_PROTOCOL' => 'HTTP/1.0',
                ],
                [
                    'method' => 'GET',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.0',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'post' => [
                [
                    'REQUEST_METHOD' => 'POST',
                ],
                [
                    'method' => 'POST',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'delete' => [
                [
                    'REQUEST_METHOD' => 'DELETE',
                ],
                [
                    'method' => 'DELETE',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'put' => [
                [
                    'REQUEST_METHOD' => 'PUT',
                ],
                [
                    'method' => 'PUT',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'http',
                    'path' => '',
                    'query' => '',
                ],
            ],
            'https' => [
                [
                    'REQUEST_METHOD' => 'PUT',
                    'HTTPS' => 'on',
                ],
                [
                    'method' => 'PUT',
                    'host' => '',
                    'port' => null,
                    'protocol' => '1.1',
                    'scheme' => 'https',
                    'path' => '',
                    'query' => '',
                ],
            ],
        ];
    }

    /**
     * @dataProvider hostParsingDataProvider
     */
    public function testHostParsingFromParameters(array $serverParams, array $expectParams): void
    {
        $serverRequest = $this
            ->createServerRequestFactory()
            ->createFromParameters($serverParams);

        $this->assertSame($expectParams['host'], $serverRequest
            ->getUri()
            ->getHost());
        $this->assertSame($expectParams['port'], $serverRequest
            ->getUri()
            ->getPort());
        $this->assertSame($expectParams['method'], $serverRequest->getMethod());
        $this->assertSame($expectParams['protocol'], $serverRequest->getProtocolVersion());
        $this->assertSame($expectParams['scheme'], $serverRequest
            ->getUri()
            ->getScheme());
        $this->assertSame($expectParams['path'], $serverRequest
            ->getUri()
            ->getPath());
        $this->assertSame($expectParams['query'], $serverRequest
            ->getUri()
            ->getQuery());
    }

    /**
     * @dataProvider hostParsingDataProvider
     * @backupGlobals enabled
     */
    public function testHostParsingFromGlobals(array $serverParams, array $expectParams): void
    {
        $_SERVER = $serverParams;
        $serverRequest = $this
            ->createServerRequestFactory()
            ->createFromGlobals();

        $this->assertSame($expectParams['host'], $serverRequest
            ->getUri()
            ->getHost());
        $this->assertSame($expectParams['port'], $serverRequest
            ->getUri()
            ->getPort());
        $this->assertSame($expectParams['method'], $serverRequest->getMethod());
        $this->assertSame($expectParams['protocol'], $serverRequest->getProtocolVersion());
        $this->assertSame($expectParams['scheme'], $serverRequest
            ->getUri()
            ->getScheme());
        $this->assertSame($expectParams['path'], $serverRequest
            ->getUri()
            ->getPath());
        $this->assertSame($expectParams['query'], $serverRequest
            ->getUri()
            ->getQuery());
    }

    public function dataJsonParsing(): array
    {
        return [
            [['name' => 'mike', 'age' => 21], '{"name":"mike","age":21}', 'application/json'],
            [['name' => 'mike', 'age' => 21], '{"name":"mike","age":21}', 'application/test+json'],
        ];
    }

    /**
     * @dataProvider dataJsonParsing
     */
    public function testJsonParsing(array $expectedParsedBody, string $body, string $contentType): void
    {
        $request = $this
            ->createServerRequestFactory()
            ->createFromParameters(
                server: ['REQUEST_METHOD' => 'POST'],
                headers: ['Content-Type' => $contentType],
                body: $body,
            );

        $this->assertSame($expectedParsedBody, $request->getParsedBody());
    }

    public function dataInvalidJsonParsing(): array
    {
        return [
            'string' => [
                'Parsed JSON must contain array, but "string" given.',
                '"test"',
            ],
            'int' => [
                'Parsed JSON must contain array, but "int" given.',
                '42',
            ],
            'invalid-json' => [
                'Error when parsing JSON request body.',
                '{42',
            ],
        ];
    }

    /**
     * @dataProvider dataInvalidJsonParsing
     */
    public function testInvalidJsonParsing(string $expectedMessage, string $body): void
    {
        $request = $this->createServerRequestFactory();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage($expectedMessage);
        $request->createFromParameters(
            server: ['REQUEST_METHOD' => 'POST'],
            headers: ['Content-Type' => 'application/json'],
            body: $body,
        );
    }

    public function dataPostInParsedBody(): array
    {
        return [
            [
                ['name' => 'test'],
                'application/x-www-form-urlencoded',
            ],
            [
                ['name' => 'test'],
                'multipart/form-data',
            ],
        ];
    }

    /**
     * @dataProvider dataPostInParsedBody
     */
    public function testPostInParsedBody(array $post, string $contentType): void
    {
        $request = $this
            ->createServerRequestFactory()
            ->createFromParameters(
                server: ['REQUEST_METHOD' => 'POST'],
                headers: ['Content-Type' => $contentType],
                post: $post,
            );

        $this->assertSame($post, $request->getParsedBody());
    }

    private function createServerRequestFactory(): ServerRequestFactory
    {
        return new ServerRequestFactory(
            new PsrServerRequestFactory(),
            new UriFactory(),
            new UploadedFileFactory(),
            new StreamFactory(),
        );
    }
}
