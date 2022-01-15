<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use ErrorException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\NotFoundException;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;
use Yiisoft\Http\Method;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Yii\Event\ListenerConfigurationChecker;
use Yiisoft\Yii\Http\Application;
use Yiisoft\Yii\Http\Handler\ThrowableHandler;
use Yiisoft\Yii\Runner\BootstrapRunner;
use Yiisoft\Yii\Runner\ConfigFactory;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;
use Yiisoft\Yii\Runner\RunnerInterface;

use function microtime;

/**
 * `HttpApplicationRunner` runs the Yii HTTP application.
 */
final class HttpApplicationRunner implements RunnerInterface
{
    private bool $debug;
    private string $rootPath;
    private ?string $environment;
    private ?ConfigInterface $config = null;
    private ?ContainerInterface $container = null;
    private ?ErrorHandler $temporaryErrorHandler = null;
    private ?string $bootstrapGroup = 'bootstrap-web';

    /**
     * @param string $rootPath The absolute path to the project root.
     * @param bool $debug Whether the debug mode is enabled.
     * @param string|null $environment The environment name.
     */
    public function __construct(string $rootPath, bool $debug, ?string $environment)
    {
        $this->rootPath = $rootPath;
        $this->debug = $debug;
        $this->environment = $environment;
    }

    /**
     * Returns a new instance with the specified bootstrap configuration group name.
     *
     * @param string $bootstrapGroup The bootstrap configuration group name.
     *
     * @return self
     */
    public function withBootstrap(string $bootstrapGroup): self
    {
        $new = clone $this;
        $new->bootstrapGroup = $bootstrapGroup;
        return $new;
    }

    /**
     * Returns a new instance and disables the use of bootstrap configuration group.
     *
     * @return self
     */
    public function withoutBootstrap(): self
    {
        $new = clone $this;
        $new->bootstrapGroup = null;
        return $new;
    }

    /**
     * Returns a new instance with the specified config instance {@see ConfigInterface}.
     *
     * @param ConfigInterface $config The config instance.
     *
     * @return self
     */
    public function withConfig(ConfigInterface $config): self
    {
        $new = clone $this;
        $new->config = $config;
        return $new;
    }

    /**
     * Returns a new instance with the specified container instance {@see ContainerInterface}.
     *
     * @param ContainerInterface $container The container instance.
     *
     * @return self
     */
    public function withContainer(ContainerInterface $container): self
    {
        $new = clone $this;
        $new->container = $container;
        return $new;
    }

    /**
     * Returns a new instance with the specified temporary error handler instance {@see ErrorHandler}.
     *
     * A temporary error handler is needed to handle the creation of configuration and container instances,
     * then the error handler configured in your application configuration will be used.
     *
     * @param ErrorHandler $temporaryErrorHandler The temporary error handler instance.
     *
     * @return self
     */
    public function withTemporaryErrorHandler(ErrorHandler $temporaryErrorHandler): self
    {
        $new = clone $this;
        $new->temporaryErrorHandler = $temporaryErrorHandler;
        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CircularReferenceException|ErrorException|HeadersHaveBeenSentException|InvalidConfigException
     * @throws ContainerExceptionInterface|NotFoundException|NotFoundExceptionInterface|NotInstantiableException
     */
    public function run(): void
    {
        $startTime = microtime(true);

        // Register temporary error handler to catch error while container is building.
        $temporaryErrorHandler = $this->createTemporaryErrorHandler();
        $this->registerErrorHandler($temporaryErrorHandler);

        $config = $this->config ?? ConfigFactory::create(new ConfigPaths($this->rootPath, 'config'), $this->environment);
        $container = $this->container ?? $this->createDefaultContainer($config);

        // Register error handler with real container-configured dependencies.
        /** @var ErrorHandler $actualErrorHandler */
        $actualErrorHandler = $container->get(ErrorHandler::class);
        $this->registerErrorHandler($actualErrorHandler, $temporaryErrorHandler);

        if ($container instanceof Container) {
            $container = $container->get(ContainerInterface::class);
        }

        // Run bootstrap
        if ($this->bootstrapGroup !== null) {
            $this->runBootstrap($container, $config->get($this->bootstrapGroup));
        }

        if ($this->debug && $config->has('events-web')) {
            /** @psalm-suppress MixedMethodCall */
            $container->get(ListenerConfigurationChecker::class)->check($config->get('events-web'));
        }

        /** @var Application $application */
        $application = $container->get(Application::class);

        /**
         * @var ServerRequestInterface
         * @psalm-suppress MixedMethodCall
         */
        $serverRequest = $container->get(ServerRequestFactory::class)->createFromGlobals();
        $request = $serverRequest->withAttribute('applicationStartTime', $startTime);

        try {
            $application->start();
            $response = $application->handle($request);
            $this->emit($request, $response);
        } catch (Throwable $throwable) {
            $handler = new ThrowableHandler($throwable);
            /**
             * @var ResponseInterface
             * @psalm-suppress MixedMethodCall
             */
            $response = $container->get(ErrorCatcher::class)->process($request, $handler);
            $this->emit($request, $response);
        } finally {
            $application->afterEmit($response ?? null);
            $application->shutdown();
        }
    }

    /**
     * @throws ErrorException|InvalidConfigException
     */
    private function createDefaultContainer(ConfigInterface $config): Container
    {
        $containerConfig = ContainerConfig::create()->withValidate($this->debug);

        if ($config->has('web')) {
            $containerConfig = $containerConfig->withDefinitions($config->get('web'));
        }

        if ($config->has('providers-web')) {
            $containerConfig = $containerConfig->withProviders($config->get('providers-web'));
        }

        if ($config->has('delegates-web')) {
            $containerConfig = $containerConfig->withDelegates($config->get('delegates-web'));
        }

        return new Container($containerConfig);
    }

    private function createTemporaryErrorHandler(): ErrorHandler
    {
        if ($this->temporaryErrorHandler !== null) {
            return $this->temporaryErrorHandler;
        }

        $logger = new Logger([new FileTarget("$this->rootPath/runtime/logs/app.log")]);
        return new ErrorHandler($logger, new HtmlRenderer());
    }

    /**
     * @throws HeadersHaveBeenSentException
     */
    private function emit(ServerRequestInterface $request, ResponseInterface $response): void
    {
        (new SapiEmitter())->emit($response, $request->getMethod() === Method::HEAD);
    }

    /**
     * @throws ErrorException
     */
    private function registerErrorHandler(ErrorHandler $registered, ErrorHandler $unregistered = null): void
    {
        $unregistered?->unregister();

        if ($this->debug) {
            $registered->debug();
        }

        $registered->register();
    }

    private function runBootstrap(ContainerInterface $container, array $bootstrapList): void
    {
        (new BootstrapRunner($container, $bootstrapList))->run();
    }
}
