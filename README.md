<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii HTTP Runner</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-runner-http/v)](https://packagist.org/packages/yiisoft/yii-runner-http)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-runner-http/downloads)](https://packagist.org/packages/yiisoft/yii-runner-http)
[![Build status](https://github.com/yiisoft/yii-runner-http/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/yii-runner-http/actions/workflows/build.yml)
[![Code coverage](https://codecov.io/gh/yiisoft/yii-runner-http/graph/badge.svg?token=C1uxQU6iLp)](https://codecov.io/gh/yiisoft/yii-runner-http)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-runner-http%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-runner-http/master)
[![static analysis](https://github.com/yiisoft/yii-runner-http/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-runner-http/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-runner-http/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-runner-http)

The package contains a bootstrap for running Yii3 HTTP application.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/yii-runner-http
```

## General usage

In your HTTP entry script do the following:

```php
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

require_once __DIR__ . '/autoload.php';

(new HttpApplicationRunner(
    rootPath: __DIR__, 
    debug: $_ENV['YII_DEBUG'],
    checkEvents: $_ENV['YII_DEBUG'],
    environment: $_ENV['YII_ENV']
))->run();
```

### Additional configuration

By default, the `HttpApplicationRunner` is configured to work with Yii application templates and follows the
[config groups convention](https://github.com/yiisoft/docs/blob/master/022-config-groups.md).

You can override the default configuration using constructor parameters and immutable setters.

#### Constructor parameters

`$rootPath` — the absolute path to the project root.

`$debug` — whether the debug mode is enabled.

`$checkEvents` — whether check events' configuration.

`$environment` — the environment name.

`$bootstrapGroup` — the bootstrap configuration group name.

`$eventsGroup` — the events' configuration group name.

`$diGroup` — the container definitions' configuration group name.

`$diProvidersGroup` — the container providers' configuration group name.

`$diDelegatesGroup` — the container delegates' configuration group name.

`$diTagsGroup` — the container tags' configuration group name.

`$paramsGroup` — the config parameters group name.

`$nestedParamsGroups` — configuration group names that are included into config parameters group. This is needed for
recursive merge parameters.

`$nestedEventsGroups` — configuration group names that are included into events' configuration group. This is needed for
reverse and recursive merge events' configurations.

`$configModifiers` — [configuration modifiers](https://github.com/yiisoft/config#configuration-modifiers).

`$configDirectory` — the relative path from `$rootPath` to the configuration storage location.

`$vendorDirectory` — the relative path from `$rootPath` to the vendor directory.

`$configMergePlanFile` — the relative path from `$configDirectory` to merge plan.

`$logger` — the logger to collect errors while container is building.

`$bufferSize` — the size of the buffer in bytes to send the content of the message body (default, 8Mb).

`$temporaryErrorHandler` — a temporary error handler that needed to handle the creation of configuration and container 
instances.

`$emitter` — an emitter to send the response.

`$useRemoveBodyByStatusMiddleware` — whether to remove the body of the response for specific response status codes.

`$useContentLengthMiddleware` — whether to manage the `Content-Length` header to the response.

`$useHeadRequestMiddleware` — whether to remove the body of the response for HEAD requests.

#### Immutable setters

If the configuration instance settings differ from the default you can specify a customized configuration instance:

```php
/**
 * @var Yiisoft\Config\ConfigInterface $config
 * @var Yiisoft\Yii\Runner\Http\HttpApplicationRunner $runner
 */

$runner = $runner->withConfig($config);
```

The default container is `Yiisoft\Di\Container`. But you can specify any implementation
of the `Psr\Container\ContainerInterface`:

```php
/**
 * @var Psr\Container\ContainerInterface $container
 * @var Yiisoft\Yii\Runner\Http\HttpApplicationRunner $runner
 */

$runner = $runner->withContainer($container);
```

In addition to the error handler that is defined in the container, the runner uses a temporary error handler.
A temporary error handler is needed to handle the creation of configuration and container instances,
then the error handler configured in your application configuration will be used.

By default, the temporary error handler uses HTML renderer and logging to a file. You can override this as follows:

```php
/**
 * @var Psr\Log\LoggerInterface $logger
 * @var Yiisoft\ErrorHandler\Renderer\PlainTextRenderer $renderer
 * @var Yiisoft\Yii\Runner\Http\HttpApplicationRunner $runner
 */

$runner = $runner->withTemporaryErrorHandler(
    new Yiisoft\ErrorHandler\ErrorHandler($logger, $renderer),
);
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii HTTP Runner is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
