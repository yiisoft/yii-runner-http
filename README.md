<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii HTTP Runner</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-runner-http/v/stable.png)](https://packagist.org/packages/yiisoft/yii-runner-http)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-runner-http/downloads.png)](https://packagist.org/packages/yiisoft/yii-runner-http)
[![Build status](https://github.com/yiisoft/yii-runner-http/workflows/build/badge.svg)](https://github.com/yiisoft/yii-runner-http/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-runner-http/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-runner-http/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-runner-http/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-runner-http/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-runner-http%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-runner-http/master)
[![static analysis](https://github.com/yiisoft/yii-runner-http/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-runner-http/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-runner-http/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-runner-http)

The package contains a bootstrap for running Yii3 HTTP application.

## Requirements

- PHP 8.0 or higher.

## Installation

The package could be installed with composer:

```shell
composer require yiisoft/yii-runner-http --prefer-dist
```

## General usage

In your HTTP entry script do the following:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

require_once __DIR__ . '/autoload.php';

(new HttpApplicationRunner(__DIR__, $_ENV['YII_DEBUG'], $_ENV['YII_ENV']))->run();
```

### Additional configuration

By default, the `HttpApplicationRunner` is configured to work with Yii application templates.
You can override the default configuration using immutable setters.

Override the name of the bootstrap configuration group as follows:

```php
/**
 * @var Yiisoft\Yii\Runner\Http\HttpApplicationRunner $runner
 */

// Bootstrap configuration group name by default is "bootstrap-web".
$runner = $runner->withBootstrap('my-bootstrap-config-group-name');

// Disables the use of bootstrap configuration group.
$runner = $runner->withoutBootstrap();
```

In debug mode, event configurations are checked, to override, use the following setters:

```php
/**
 * @var Yiisoft\Yii\Runner\Http\HttpApplicationRunner $runner
 */

// Configuration group name of events by default is "events-web".
$runner = $runner->withCheckingEvents('my-events-config-group-name');

// Disables checking of the event configuration group.
$runner = $runner->withoutCheckingEvents();
```

If the configuration instance settings differ from the default, such as configuration group names,
you can specify a customized configuration instance:

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

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

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
