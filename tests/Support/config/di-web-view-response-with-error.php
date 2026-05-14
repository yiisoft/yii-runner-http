<?php

declare(strict_types=1);

use HttpSoft\Message\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Runner\Http\Tests\Support\ViewResponse;

return [
    'applicationMiddleware' => new class implements MiddlewareInterface {
        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {
            $response = (new ResponseFactory())->createResponse();

            return new ViewResponse(
                $response,
                fn(): StreamInterface => throw new Exception('Failure while creating response stream'),
            );
        }
    },
];
