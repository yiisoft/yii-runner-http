<?php

declare(strict_types=1);

use HttpSoft\Message\Response;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

return [
    'applicationMiddleware' => new class () implements MiddlewareInterface {
        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ): ResponseInterface {
            return (new Response())
                ->withBody((new StreamFactory())->createStream('OK'))
                ->withStatus(Status::CONTINUE);
        }
    },
];
