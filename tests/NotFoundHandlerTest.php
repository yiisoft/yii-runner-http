<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Yii\Runner\Http\NotFoundHandler;

final class NotFoundHandlerTest extends TestCase
{
    public function testShouldReturnCode404(): void
    {
        $response = $this->createHandler()->handle($this->createRequest());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testShouldReturnCorrectErrorInBody(): void
    {
        $response = $this->createHandler()->handle($this->createRequest('https://example.com/test/path?param=1'));
        $this->assertSame('We were unable to find the page "/test/path".', (string) $response->getBody());
    }

    private function createHandler(): NotFoundHandler
    {
        return new NotFoundHandler(new ResponseFactory());
    }

    private function createRequest(string $uri = '/'): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest(Method::GET, $uri);
    }
}
