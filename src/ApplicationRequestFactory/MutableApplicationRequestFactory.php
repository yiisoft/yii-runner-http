<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\ApplicationRequestFactory;

use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MutableApplicationRequestFactory implements ApplicationRequestFactoryInterface
{
    private ?ServerRequestInterface $request = null;

    public function create(ContainerInterface $container): ServerRequestInterface
    {
        return $this->request ?? throw new LogicException('Request is not set.');
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}
