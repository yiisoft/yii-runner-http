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
     * Sends the response to the client with headers and body.
     *
     * @param ResponseInterface $response Response object to send.
     *
     * @throws HeadersHaveBeenSentException
     */
    public function emit(ResponseInterface $response): void;
}
