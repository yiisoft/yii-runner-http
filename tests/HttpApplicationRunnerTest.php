<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests;

use Exception;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\Di\Container;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\ErrorHandler\Renderer\PlainTextRenderer;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Yii\Http\Application;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Http\Event\BeforeRequest;
use Yiisoft\Yii\Http\NotFoundHandler;
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

final class HttpApplicationRunnerTest extends TestCase
{
    private HttpApplicationRunner $runner;

    public function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->runner = new HttpApplicationRunner(__DIR__ . '/Support', true, null);
    }

    public function testRun(): void
    {
        $this->expectOutputString('OK');

        $this->runner->run();
    }

    public function testRunWithoutBootstrapAndEvents(): void
    {
        $runner = $this->runner->withoutBootstrap()->withoutEvents();

        $this->expectOutputString('OK');

        $runner->run();
    }

    public function testRunWithCustomizedConfiguration(): void
    {
        $container = $this->createContainer();

        $runner = $this->runner
            ->withContainer($container)
            ->withConfig($this->createConfig())
            ->withTemporaryErrorHandler($this->createErrorHandler())
        ;

        $runner->run();

        /** @var SimpleEventDispatcher $dispatcher */
        $dispatcher = $container->get(EventDispatcherInterface::class);

        $this->assertSame(
            [
                ApplicationStartup::class,
                BeforeRequest::class,
                BeforeMiddleware::class,
                AfterMiddleware::class,
                AfterRequest::class,
                AfterEmit::class,
                ApplicationShutdown::class,
            ],
            $dispatcher->getEventClasses(),
        );
    }

    public function testRunWithFailureDuringProcess(): void
    {
        $runner = $this->runner->withContainer($this->createContainer(true));

        $this->expectOutputRegex("/^Exception with message 'Failure'/");

        $runner->run();
    }

    public function testImmutability(): void
    {
        $this->assertNotSame($this->runner, $this->runner->withBootstrap('bootstrap-web'));
        $this->assertNotSame($this->runner, $this->runner->withoutBootstrap());
        $this->assertNotSame($this->runner, $this->runner->withEvents('events-web'));
        $this->assertNotSame($this->runner, $this->runner->withoutEvents());
        $this->assertNotSame($this->runner, $this->runner->withConfig($this->createConfig()));
        $this->assertNotSame($this->runner, $this->runner->withContainer($this->createContainer()));
        $this->assertNotSame($this->runner, $this->runner->withTemporaryErrorHandler($this->createErrorHandler()));
    }

    private function createContainer(bool $throwException = false): ContainerInterface
    {
        return new Container([
            EventDispatcherInterface::class => SimpleEventDispatcher::class,
            LoggerInterface::class => SimpleLogger::class,
            MiddlewareFactoryInterface::class => MiddlewareFactory::class,
            ResponseFactoryInterface::class => ResponseFactory::class,
            ServerRequestFactoryInterface::class => ServerRequestFactory::class,
            StreamFactoryInterface::class => StreamFactory::class,
            ThrowableRendererInterface::class => PlainTextRenderer::class,
            UriFactoryInterface::class => UriFactory::class,
            UploadedFileFactoryInterface::class => UploadedFileFactory::class,

            ErrorCatcher::class => [
                'forceContentType()' => ['text/plain'],
            ],

            Application::class => [
                '__construct()' => [
                    'dispatcher' => DynamicReference::to(
                        static function (ContainerInterface $container) use ($throwException) {
                            return $container->get(MiddlewareDispatcher::class)->withMiddlewares([
                                static fn () => new class ($throwException) implements MiddlewareInterface {
                                    private bool $throwException;

                                    public function __construct(bool $throwException)
                                    {
                                        $this->throwException = $throwException;
                                    }

                                    public function process(
                                        ServerRequestInterface $request,
                                        RequestHandlerInterface $handler
                                    ): ResponseInterface {
                                        if ($this->throwException) {
                                            throw new Exception('Failure');
                                        }

                                        return (new ResponseFactory())->createResponse();
                                    }
                                },
                            ]);
                        },
                    ),
                    'fallbackHandler' => Reference::to(NotFoundHandler::class),
                ],
            ],
        ]);
    }

    private function createConfig(): Config
    {
        return new Config(new ConfigPaths(__DIR__ . '/Support', 'config'));
    }

    private function createErrorHandler(): ErrorHandler
    {
        return new ErrorHandler(new SimpleLogger(), new PlainTextRenderer());
    }
}
