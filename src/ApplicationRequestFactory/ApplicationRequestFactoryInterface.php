<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\ApplicationRequestFactory;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApplicationRequestFactoryInterface
{
    public function create(ContainerInterface $container): ServerRequestInterface;
}
