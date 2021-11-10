<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Event;

use Psr\Http\Message\ServerRequestInterface;

final class BeforeRequest
{
    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
