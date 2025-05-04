<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;

interface EmitterInterface
{
    /**
     * @throws HeadersHaveBeenSentException
     */
    public function emit(ResponseInterface $response): void;

    /**
     * @throws HeadersHaveBeenSentException
     */
    public function emitHeaders(ResponseInterface $response): void;
}
