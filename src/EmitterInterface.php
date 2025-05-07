<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;

/**
 * `EmitterInterface` is responsible for sending HTTP responses.
 */
interface EmitterInterface
{
    /**
     * Sends the response to the client.
     *
     * @throws HeadersHaveBeenSentException
     */
    public function emit(ResponseInterface $response): void;

    /**
     * Sends only the headers of the response to the client.
     *
     * @throws HeadersHaveBeenSentException
     */
    public function emitHeaders(ResponseInterface $response): void;
}
