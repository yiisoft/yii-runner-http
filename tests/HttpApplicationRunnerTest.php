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
use Yiisoft\Di\ContainerConfig;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\ErrorHandler\Renderer\PlainTextRenderer;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Yii\Http\Application;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Http\Event\BeforeRequest;
use Yiisoft\Yii\Http\Handler\NotFoundHandler;
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

final class HttpApplicationRunnerTest extends TestCase
{
    public function testRun1(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $runner = new HttpApplicationRunner(__DIR__ . '/Support/apps/default');

        $this->expectOutputString('OK');
        $runner->run();
    }

    public function testRunWithoutBootstrapAndCheckEvents(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $runner = new HttpApplicationRunner(
            rootPath: __DIR__ . '/Support/apps/default',
            checkEvents: false,
        );

        $this->expectOutputString('OK');
        $runner->run();
    }

    public function testRunWithCustomizedConfiguration(): void
    {
        $container = $this->createContainer();
        $config = new Config(new ConfigPaths(__DIR__ . '/Support/apps/default', 'config'), paramsGroup: 'params-web');

        $runner = (new HttpApplicationRunner(__DIR__ . '/Support/apps/default'))
            ->withContainer($container)
            ->withConfig($config)
            ->withTemporaryErrorHandler($this->createErrorHandler());

        $_SERVER['REQUEST_METHOD'] = 'GET';
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
        $container = $this->createContainer(true);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $runner = (new HttpApplicationRunner(__DIR__ . '/Support/apps/default', debug: true))
            ->withContainer($container);

        $this->expectOutputRegex("/^Exception with message 'Failure'/");
        $runner->run();
    }

    public function testImmutability(): void
    {
        $config = new Config(new ConfigPaths(__DIR__ . '/Support/apps/default', 'config'));
        $runner = new HttpApplicationRunner(__DIR__ . '/Support/apps/default');

        $this->assertNotSame($runner, $runner->withConfig($config));
        $this->assertNotSame($runner, $runner->withContainer($this->createContainer()));
        $this->assertNotSame($runner, $runner->withTemporaryErrorHandler($this->createErrorHandler()));
    }

    private function createContainer(bool $throwException = false): ContainerInterface
    {
        $containerConfig = ContainerConfig::create()
            ->withDefinitions($this->createDefinitions($throwException));
        return new Container($containerConfig);
    }

    private function createDefinitions(bool $throwException): array
    {
        return [
            EventDispatcherInterface::class => SimpleEventDispatcher::class,
            LoggerInterface::class => SimpleLogger::class,
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
                            return $container
                                ->get(MiddlewareDispatcher::class)
                                ->withMiddlewares([
                                    static fn() => new class ($throwException) implements MiddlewareInterface {
                                        public function __construct(private bool $throwException)
                                        {
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
        ];
    }

    private function createErrorHandler(): ErrorHandler
    {
        return new ErrorHandler(new SimpleLogger(), new PlainTextRenderer());
    }
}
