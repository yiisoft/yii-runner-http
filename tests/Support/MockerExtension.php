<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests\Support;

use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;
use Xepozz\InternalMocker\Mocker;
use Xepozz\InternalMocker\MockerState;
use Yiisoft\Yii\Runner\Http\Tests\Support\Emitter\HTTPFunctions;

final class MockerExtension implements BeforeTestHook, BeforeFirstTestHook
{
    public function executeBeforeFirstTest(): void
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
                'function' => fn(string $string, bool $replace = true, ?int $http_response_code = null) => HTTPFunctions::header($string, $replace, $http_response_code),
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

    public function executeBeforeTest(string $test): void
    {
        MockerState::resetState();
    }
}
