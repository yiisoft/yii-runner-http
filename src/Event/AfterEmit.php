<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Event;

use Psr\Http\Message\ResponseInterface;

final class AfterEmit
{
    private ?ResponseInterface $response;

    public function __construct(?ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
