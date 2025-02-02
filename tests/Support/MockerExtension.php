<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests\Support;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Xepozz\InternalMocker\Mocker;
use Xepozz\InternalMocker\MockerState;
use Yiisoft\Yii\Runner\Http\Tests\Support\Emitter\HTTPFunctions;

final class MockerExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new class () implements StartedSubscriber {
                public function notify(Started $event): void
                {
                    MockerExtension::load();
                }
            },
            new class () implements PreparationStartedSubscriber {
                public function notify(PreparationStarted $event): void
                {
                    MockerState::resetState();
                }
            },
        );
    }

    public static function load(): void
    {
        $mocks = [
            [
                'namespace' => '',
                'name' => 'http_response_code',
                'function' => fn(?int $response_code = null) => HTTPFunctions::http_response_code($response_code),
            ],
            [
                'namespace' => '',
                'name' => 'header',
                'function' => fn(
                    string $string,
                    bool $replace = true,
                    ?int $http_response_code = null
                ) => HTTPFunctions::header($string, $replace, $http_response_code),
            ],
            [
                'namespace' => '',
                'name' => 'headers_sent',
                'function' => fn(&$file = null, &$line = null) => HTTPFunctions::headers_sent($file, $line),
            ],
            [
                'namespace' => '',
                'name' => 'header_remove',
                'function' => fn() => HTTPFunctions::header_remove(),
            ],
            [
                'namespace' => '',
                'name' => 'header_list',
                'function' => fn() => HTTPFunctions::headers_list(),
            ],
            [
                'namespace' => '',
                'name' => 'flush',
                'function' => fn() => HTTPFunctions::flush(),
            ],
        ];

        $mocker = new Mocker();
        $mocker->load($mocks);
        MockerState::saveState();
    }
}
