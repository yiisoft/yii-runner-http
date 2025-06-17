<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use ErrorException;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Di\NotFoundException;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;
use Yiisoft\Http\Header;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\PsrEmitter\EmitterInterface;
use Yiisoft\PsrEmitter\FakeEmitter;
use Yiisoft\PsrEmitter\HeadersHaveBeenSentException as EmitterHeadersHaveBeenSentException;
use Yiisoft\PsrEmitter\SapiEmitter;
use Yiisoft\Yii\Http\Application;
use Yiisoft\Yii\Http\Handler\ThrowableHandler;
use Yiisoft\Yii\Runner\ApplicationRunner;
use Yiisoft\Yii\Runner\Http\ApplicationRequestFactory\ApplicationRequestFactoryInterface;
use Yiisoft\Yii\Runner\Http\ApplicationRequestFactory\InternalApplicationRequestFactory;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;

use function in_array;
use function microtime;

/**
 * `HttpApplicationRunner` runs the Yii HTTP application.
 */
final class HttpApplicationRunner extends ApplicationRunner
{
    private readonly EmitterInterface $emitter;
    private ?FakeEmitter $fakeEmitter = null;

    /**
     * @param string $rootPath The absolute path to the project root.
     * @param bool $debug Whether the debug mode is enabled.
     * @param bool $checkEvents Whether to check events' configuration.
     * @param string|null $environment The environment name.
     * @param string $bootstrapGroup The bootstrap configuration group name.
     * @param string $eventsGroup The events' configuration group name.
     * @param string $diGroup The container definitions' configuration group name.
     * @param string $diProvidersGroup The container providers' configuration group name.
     * @param string $diDelegatesGroup The container delegates' configuration group name.
     * @param string $diTagsGroup The container tags' configuration group name.
     * @param string $paramsGroup The configuration parameters group name.
     * @param array $nestedParamsGroups Configuration group names that are included into configuration parameters group.
     * This is needed for recursive merging of parameters.
     * @param array $nestedEventsGroups Configuration group names that are included into events' configuration group.
     * This is needed for reverse and recursive merge of events' configurations.
     * @param object[] $configModifiers Modifiers for {@see Config}.
     * @param string $configDirectory The relative path from {@see $rootPath} to the configuration storage location.
     * @param string $vendorDirectory The relative path from {@see $rootPath} to the vendor directory.
     * @param string $configMergePlanFile The relative path from {@see $configDirectory} to merge plan.
     * @param LoggerInterface|null $logger (deprecated) The logger to collect errors while container is building.
     * @param int|null $bufferSize The size of the buffer in bytes to send the content of the message body.
     * Deprecated and will be removed in the next major version. Use custom emitter instead.
     * @param ErrorHandler|null $temporaryErrorHandler The temporary error handler instance that used to handle
     * the creation of configuration and container instances, then the error handler configured in your application
     * configuration will be used.
     * @param EmitterInterface|null $emitter The emitter instance to send the response with. By default, it uses
     * {@see SapiEmitter}.
     * @param bool $useRemoveBodyByStatusMiddleware Whether to remove the body of the response for specific response
     * status codes. Deprecated and will be removed in the next major version. Use `RemoveBodyMiddleware` from
     * {@see https://github.com/yiisoft/http-middleware/} instead.
     * @param bool $useContentLengthMiddleware Whether to manage the `Content-Length` header to the response. Deprecated
     * and will be removed in the next major version. Use `ContentLengthMiddleware` from
     * {@see https://github.com/yiisoft/http-middleware/} instead.
     * @param bool $useHeadRequestMiddleware Whether to remove the body of the response for HEAD requests. Deprecated
     * and will be removed in the next major version. Use `HeadRequestMiddleware` from
     * {@see https://github.com/yiisoft/http-middleware/} instead.
     *
     * @psalm-param list<string> $nestedParamsGroups
     * @psalm-param list<string> $nestedEventsGroups
     * @psalm-param list<object> $configModifiers
     */
    public function __construct(
        string $rootPath,
        bool $debug = false,
        bool $checkEvents = false,
        ?string $environment = null,
        string $bootstrapGroup = 'bootstrap-web',
        string $eventsGroup = 'events-web',
        string $diGroup = 'di-web',
        string $diProvidersGroup = 'di-providers-web',
        string $diDelegatesGroup = 'di-delegates-web',
        string $diTagsGroup = 'di-tags-web',
        string $paramsGroup = 'params-web',
        array $nestedParamsGroups = ['params'],
        array $nestedEventsGroups = ['events'],
        array $configModifiers = [],
        string $configDirectory = 'config',
        string $vendorDirectory = 'vendor',
        string $configMergePlanFile = '.merge-plan.php',
        private readonly ?LoggerInterface $logger = null,
        ?int $bufferSize = null,
        private ?ErrorHandler $temporaryErrorHandler = null,
        ?EmitterInterface $emitter = null,
        private readonly bool $useRemoveBodyByStatusMiddleware = true,
        private readonly bool $useContentLengthMiddleware = true,
        private readonly bool $useHeadRequestMiddleware = true,
        private readonly ApplicationRequestFactoryInterface $requestFactory = new InternalApplicationRequestFactory(),
    ) {
        $this->emitter = $emitter ?? new SapiEmitter($bufferSize);

        parent::__construct(
            $rootPath,
            $debug,
            $checkEvents,
            $environment,
            $bootstrapGroup,
            $eventsGroup,
            $diGroup,
            $diProvidersGroup,
            $diDelegatesGroup,
            $diTagsGroup,
            $paramsGroup,
            $nestedParamsGroups,
            $nestedEventsGroups,
            $configModifiers,
            $configDirectory,
            $vendorDirectory,
            $configMergePlanFile,
        );
    }

    /**
     * @deprecated Use `$temporaryErrorHandler` constructor parameter instead.
     *
     * Returns a new instance with the specified temporary error handler instance {@see ErrorHandler}.
     *
     * A temporary error handler is needed to handle the creation of configuration and container instances,
     * then the error handler configured in your application configuration will be used.
     *
     * @param ErrorHandler $temporaryErrorHandler The temporary error handler instance.
     */
    public function withTemporaryErrorHandler(ErrorHandler $temporaryErrorHandler): self
    {
        $new = clone $this;
        $new->temporaryErrorHandler = $temporaryErrorHandler;
        return $new;
    }

    /**
     * @throws CircularReferenceException|ErrorException|HeadersHaveBeenSentException|InvalidConfigException
     * @throws ContainerExceptionInterface|NotFoundException|NotFoundExceptionInterface|NotInstantiableException
     */
    public function run(): void
    {
        $this->runInternal($this->emitter);
    }

    /**
     * Runs the application and gets the response instead of emitting it.
     * This method is useful for testing purposes or when you want to handle the response.
     *
     * @param ServerRequestInterface|null $request The server request to handle (optional).
     * @throws CircularReferenceException|ErrorException|HeadersHaveBeenSentException|InvalidConfigException
     * @throws ContainerExceptionInterface|NotFoundException|NotFoundExceptionInterface|NotInstantiableException
     * @return ResponseInterface The response generated by the application.
     */
    public function runAndGetResponse(?ServerRequestInterface $request = null): ResponseInterface
    {
        $this->runInternal(
            $this->fakeEmitter ??= new FakeEmitter(),
            $request,
        );
        return $this->fakeEmitter->getLastResponse()
            ?? throw new LogicException('No response was emitted.');
    }

    /**
     * @throws CircularReferenceException|ErrorException|HeadersHaveBeenSentException|InvalidConfigException
     * @throws ContainerExceptionInterface|NotFoundException|NotFoundExceptionInterface|NotInstantiableException
     */
    private function runInternal(EmitterInterface $emitter, ?ServerRequestInterface $request = null): void
    {
        $startTime = microtime(true);

        // Register temporary error handler to catch error while container is building.
        $temporaryErrorHandler = $this->createTemporaryErrorHandler();
        $this->registerErrorHandler($temporaryErrorHandler);

        $container = $this->getContainer();

        /**
         * Register error handler with real container-configured dependencies.
         * @var ErrorHandler $actualErrorHandler
         */
        $actualErrorHandler = $container->get(ErrorHandler::class);
        $this->registerErrorHandler($actualErrorHandler, $temporaryErrorHandler);

        $this->runBootstrap();
        $this->checkEvents();

        /** @var Application $application */
        $application = $container->get(Application::class);

        $request ??= $this->requestFactory->create($container);
        $request = $request->withAttribute('applicationStartTime', $startTime);
        try {
            $application->start();
            $response = $application->handle($request);
            $this->emit($emitter, $request, $response);
        } catch (Throwable $throwable) {
            $handler = new ThrowableHandler($throwable);
            /**
             * @var ResponseInterface
             * @psalm-suppress MixedMethodCall
             */
            $response = $container
                ->get(ErrorCatcher::class)
                ->process($request, $handler);
            $this->emit($emitter, $request, $response);
        } finally {
            $application->afterEmit($response ?? null);
            $application->shutdown();
        }
    }

    private function createTemporaryErrorHandler(): ErrorHandler
    {
        return $this->temporaryErrorHandler ??
            new ErrorHandler(
                $this->logger ?? new NullLogger(),
                new HtmlRenderer(),
            );
    }

    /**
     * @throws HeadersHaveBeenSentException
     */
    private function emit(EmitterInterface $emitter, ServerRequestInterface $request, ResponseInterface $response): void
    {
        $response = $this->removeBodyByStatusMiddleware($response);
        $response = $this->contentLengthMiddleware($response);
        $response = $this->headRequestMiddleware($request, $response);
        try {
            $emitter->emit($response);
        } catch (EmitterHeadersHaveBeenSentException) {
            throw new HeadersHaveBeenSentException();
        }
    }

    /**
     * @throws ErrorException
     */
    private function registerErrorHandler(ErrorHandler $registered, ?ErrorHandler $unregistered = null): void
    {
        $unregistered?->unregister();

        if ($this->debug) {
            $registered->debug();
        }

        $registered->register();
    }

    private function removeBodyByStatusMiddleware(ResponseInterface $response): ResponseInterface
    {
        if (!$this->useRemoveBodyByStatusMiddleware) {
            return $response;
        }

        if (!in_array(
            $response->getStatusCode(),
            [
                Status::CONTINUE,
                Status::SWITCHING_PROTOCOLS,
                Status::PROCESSING,
                Status::NO_CONTENT,
                Status::RESET_CONTENT,
                Status::NOT_MODIFIED,
            ],
            true
        )) {
            return $response;
        }

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $this->getContainer()->get(StreamFactoryInterface::class);
        $emptyBody = $streamFactory->createStream();

        return $response->withBody($emptyBody);
    }

    private function contentLengthMiddleware(ResponseInterface $response): ResponseInterface
    {
        if (!$this->useContentLengthMiddleware) {
            return $response;
        }

        if ($response->hasHeader(Header::TRANSFER_ENCODING)) {
            return $response->withoutHeader(Header::CONTENT_LENGTH);
        }

        if ($response->hasHeader('Content-Length')) {
            return $response;
        }

        if (in_array(
            $response->getStatusCode(),
            [
                Status::CONTINUE,
                Status::SWITCHING_PROTOCOLS,
                Status::PROCESSING,
                Status::NO_CONTENT,
                Status::RESET_CONTENT,
                Status::NOT_MODIFIED,
            ],
            true
        )) {
            return $response;
        }

        $body = $response->getBody();
        if (!$body->isReadable()) {
            return $response;
        }

        $contentLength = $response->getBody()->getSize();
        if ($contentLength === null || $contentLength === 0) {
            return $response;
        }

        return $response->withHeader(Header::CONTENT_LENGTH, (string) $contentLength);
    }

    private function headRequestMiddleware(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        if (!$this->useHeadRequestMiddleware) {
            return $response;
        }

        if ($request->getMethod() !== Method::HEAD) {
            return $response;
        }

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $this->getContainer()->get(StreamFactoryInterface::class);
        $emptyBody = $streamFactory->createStream();

        return $response->withBody($emptyBody);
    }
}
