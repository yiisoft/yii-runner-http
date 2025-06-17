<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\ApplicationRequestFactory;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Runner\Http\RequestFactory;

final class InternalApplicationRequestFactory implements ApplicationRequestFactoryInterface
{
    public function create(ContainerInterface $container): ServerRequestInterface
    {
        return $container->get(RequestFactory::class)->create();
    }
}
