<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\PsrEmitter\EmitterInterface;
use Yiisoft\PsrEmitter\HeadersHaveBeenSentException;

final class EmitterWithHeadersHaveBeenSentException implements EmitterInterface
{
    public function emit(ResponseInterface $response): void
    {
        throw new HeadersHaveBeenSentException();
    }
}
