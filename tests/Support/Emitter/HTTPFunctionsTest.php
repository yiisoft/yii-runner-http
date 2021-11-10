<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests\Support\Emitter;

include 'httpFunctionMocks.php';

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Status;

final class HTTPFunctionsTest extends TestCase
{
    public function setUp(): void
    {
        HTTPFunctions::reset();
    }

    public static function tearDownAfterClass(): void
    {
        HTTPFunctions::reset();
    }

    public function testInitialState(): void
    {
        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertSame([], $this->getHeaders());
        $this->assertFalse(HTTPFunctions::headers_sent());
    }

    public function testHeaderAndHasHeader(): void
    {
        $this->assertFalse(HTTPFunctions::hasHeader('x-test'));

        HTTPFunctions::header('X-Test: 1');

        $this->assertTrue(HTTPFunctions::hasHeader('x-test'));
    }

    public function testReset(): void
    {
        HTTPFunctions::header('X-Test: 1');
        HTTPFunctions::header('X-Test: 2', false, Status::INTERNAL_SERVER_ERROR);
        HTTPFunctions::set_headers_sent(true, 'test', 123);

        HTTPFunctions::reset();

        $this->assertSame(Status::OK, $this->getResponseCode());
        $this->assertSame([], $this->getHeaders());
        $this->assertFalse(HTTPFunctions::headers_sent($file, $line));
        $this->assertSame('', $file);
        $this->assertSame(0, $line);
    }

    public function testHeadersSent(): void
    {
        HTTPFunctions::set_headers_sent(true, 'path/to/test/file.php', 123);

        $this->assertTrue(HTTPFunctions::headers_sent($file, $line));
        $this->assertSame('path/to/test/file.php', $file);
        $this->assertSame(123, $line);
    }

    public function testAddedHeaders(): void
    {
        // first header
        HTTPFunctions::header('X-Test: 1');
        // added header with new status
        HTTPFunctions::header('X-Test: 2', false, Status::INTERNAL_SERVER_ERROR);
        HTTPFunctions::header('X-Test: 3', false);

        $this->assertContains('X-Test: 1', $this->getHeaders());
        $this->assertContains('X-Test: 2', $this->getHeaders());
        $this->assertContains('X-Test: 3', $this->getHeaders());
        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $this->getResponseCode());
    }

    public function testReplacingHeaders(): void
    {
        HTTPFunctions::header('X-Test: 1');
        HTTPFunctions::header('X-Test: 2', false, Status::MULTIPLE_CHOICES);
        HTTPFunctions::header('X-Test: 3', false);

        // replace x-test headers with new status
        HTTPFunctions::header('X-Test: 42', true, Status::NOT_FOUND);

        $this->assertSame(['X-Test: 42'], $this->getHeaders());
        $this->assertSame(Status::NOT_FOUND, $this->getResponseCode());
    }

    public function testHeaderRemove(): void
    {
        HTTPFunctions::header('X-Test: 1');
        HTTPFunctions::header('Y-Test: 2');
        HTTPFunctions::header('Z-Test: 3', false, Status::NOT_FOUND);

        HTTPFunctions::header_remove('y-test');

        $this->assertSame(['X-Test: 1', 'Z-Test: 3'], $this->getHeaders());
    }

    public function testHeaderRemoveAll(): void
    {
        HTTPFunctions::header('X-Test: 1');
        HTTPFunctions::header('Y-Test: 2');
        HTTPFunctions::header('Z-Test: 3', false, Status::NOT_FOUND);

        HTTPFunctions::header_remove();

        $this->assertSame(Status::NOT_FOUND, $this->getResponseCode());
        $this->assertSame([], $this->getHeaders());
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
