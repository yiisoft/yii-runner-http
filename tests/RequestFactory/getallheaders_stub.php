<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use function function_exists;

if (!function_exists(__NAMESPACE__ . '\getallheaders')) {
    function getallheaders(): array|false
    {
        return Tests\RequestFactory\RequestFactoryTest::getAllHeadersStubResult();
    }
}
