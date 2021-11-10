<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests;

use Exception;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Yii\Runner\Http\Event\AfterEmit;
use Yiisoft\Yii\Runner\Http\Event\AfterRequest;
use Yiisoft\Yii\Runner\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Runner\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Runner\Http\Event\BeforeRequest;
use Yiisoft\Yii\Runner\Http\NotFoundHandler;
use Yiisoft\Yii\Runner\Http\ServerRequestHandler;

final class ServerRequestHandlerTest extends TestCase
{
    public function testStartMethodDispatchEvent(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $this->createApplication($eventDispatcher)->start();
        $this->assertSame([ApplicationStartup::class], $eventDispatcher->getEventClasses());
    }

    public function testShutdownMethodDispatchEvent(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $this->createApplication($eventDispatcher)->shutdown();
        $this->assertSame([ApplicationShutdown::class], $eventDispatcher->getEventClasses());
    }

    public function testAfterEmitMethodDispatchEvent(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $this->createApplication($eventDispatcher)->afterEmit(null);
        $this->assertSame([AfterEmit::class], $eventDispatcher->getEventClasses());
        $this->assertNull($eventDispatcher->getEvents()[0]->getResponse());
    }

    public function testAfterEmitMethodWithResponseDispatchEvent(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $this->createApplication($eventDispatcher)->afterEmit(new Response());
        $this->assertSame([AfterEmit::class], $eventDispatcher->getEventClasses());
        $this->assertInstanceOf(Response::class, $eventDispatcher->getEvents()[0]->getResponse());
    }

    public function testHandleMethodDispatchEvents(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $response = $this->createApplication($eventDispatcher, Status::NOT_FOUND)->handle($this->createRequest());

        $this->assertSame(
            [
                BeforeRequest::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterRequest::class,
            ],
            $eventDispatcher->getEventClasses(),
        );

        $this->assertSame(Status::NOT_FOUND, $response->getStatusCode());
    }

    public function testHandleMethodWithExceptionDispatchEvents(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();

        try {
            $this->createApplication($eventDispatcher,Status::OK, true)->handle($this->createRequest());
        } catch (Exception $e) {
        }

        $this->assertSame(
            [
                BeforeRequest::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterRequest::class,
            ],
            $eventDispatcher->getEventClasses(),
        );
    }

    public function testAfterRequestWithResponseDispatchEvent(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $this->createApplication($eventDispatcher)->handle($this->createRequest());
        $this->assertCount(4, $eventDispatcher->getEvents());
        $this->assertInstanceOf(Response::class, $eventDispatcher->getEvents()[3]->getResponse());
    }

    public function testAfterRequestWithExceptionDispatchEvent(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();

        try {
            $this->createApplication($eventDispatcher, Status::OK, true)->handle($this->createRequest());
        } catch (Exception $exception) {
        }

        $this->assertCount(4, $eventDispatcher->getEvents());
        $this->assertNull($eventDispatcher->getEvents()[3]->getResponse());
    }

    private function createApplication(
        EventDispatcherInterface $eventDispatcher,
        int $responseCode = Status::OK,
        bool $throwException = false
    ): ServerRequestHandler {
        if ($throwException === false) {
            $middlewareDispatcher = $this->createMiddlewareDispatcher(
                $this->createContainer($eventDispatcher),
                $responseCode,
            );
        } else {
            $middlewareDispatcher = $this->createMiddlewareDispatcherWithException(
                $this->createContainer($eventDispatcher)
            );
        }

        return new ServerRequestHandler(
            $middlewareDispatcher,
            $eventDispatcher,
            new NotFoundHandler(new ResponseFactory())
        );
    }

    private function createMiddlewareDispatcher(Container $container, int $responseCode = 200): MiddlewareDispatcher
    {
        return (new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $container->get(EventDispatcherInterface::class))
        )->withMiddlewares([
            static fn () => new class ($responseCode) implements MiddlewareInterface {
                private int $responseCode;

                public function __construct(int $responseCode)
                {
                    $this->responseCode = $responseCode;
                }

                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    return new Response($this->responseCode);
                }
            },
        ]);
    }

    private function createMiddlewareDispatcherWithException(Container $container): MiddlewareDispatcher
    {
        return (new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $container->get(EventDispatcherInterface::class))
        )->withMiddlewares([
            static fn () => new class () implements MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    throw new Exception();
                }
            },
        ]);
    }

    private function createContainer(EventDispatcherInterface $eventDispatcher): Container
    {
        return new Container(
            [
                ResponseFactoryInterface::class => new ResponseFactory(),
                EventDispatcherInterface::class => $eventDispatcher,
            ]
        );
    }

    private function createRequest(): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest(Method::GET, 'https://example.com');
    }
}
