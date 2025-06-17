<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\PsrEmitter\FakeEmitter;
use Yiisoft\Yii\Runner\Http\ApplicationRequestFactory\MutableApplicationRequestFactory;

final class TestRunner
{
    private MutableApplicationRequestFactory $requestFactory;
    private FakeEmitter $fakeEmitter;
    private HttpApplicationRunner $runner;

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
        ?LoggerInterface $logger = null,
        ?ErrorHandler $temporaryErrorHandler = null,
        bool $useRemoveBodyByStatusMiddleware = true,
        bool $useContentLengthMiddleware = true,
        bool $useHeadRequestMiddleware = true,
    ) {
        $this->requestFactory = new MutableApplicationRequestFactory();
        $this->fakeEmitter = new FakeEmitter();
        $this->runner = new HttpApplicationRunner(
            rootPath: $rootPath,
            debug: $debug,
            checkEvents: $checkEvents,
            environment: $environment,
            bootstrapGroup: $bootstrapGroup,
            eventsGroup: $eventsGroup,
            diGroup: $diGroup,
            diProvidersGroup: $diProvidersGroup,
            diDelegatesGroup: $diDelegatesGroup,
            diTagsGroup: $diTagsGroup,
            paramsGroup: $paramsGroup,
            nestedParamsGroups: $nestedParamsGroups,
            nestedEventsGroups: $nestedEventsGroups,
            configModifiers: $configModifiers,
            configDirectory: $configDirectory,
            vendorDirectory: $vendorDirectory,
            configMergePlanFile: $configMergePlanFile,
            logger: $logger,
            temporaryErrorHandler: $temporaryErrorHandler,
            emitter: $this->fakeEmitter,
            useRemoveBodyByStatusMiddleware: $useRemoveBodyByStatusMiddleware,
            useContentLengthMiddleware: $useContentLengthMiddleware,
            useHeadRequestMiddleware: $useHeadRequestMiddleware,
            requestFactory: new MutableApplicationRequestFactory(),
        );
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        $this->requestFactory->setRequest($request);
        $this->runner->run();
        return $this->fakeEmitter->getLastResponse()
            ?? throw new LogicException('No response was emitted.');
    }
}
