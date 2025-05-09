<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;

use function flush;
use function headers_sent;
use function sprintf;

/**
 * `SapiEmitter` sends a response using standard PHP Server API, i.e., with {@see header()} and "echo".
 */
final class SapiEmitter implements EmitterInterface
{
    private const DEFAULT_BUFFER_SIZE = 8_388_608; // 8MB

    private int $bufferSize;

    /**
     * @param int|null $bufferSize The size of the buffer in bytes to send the content of the message body.
     */
    public function __construct(?int $bufferSize = null)
    {
        if ($bufferSize !== null && $bufferSize < 1) {
            throw new InvalidArgumentException('Buffer size must be greater than zero.');
        }

        $this->bufferSize = $bufferSize ?? self::DEFAULT_BUFFER_SIZE;
    }

    public function emit(ResponseInterface $response): void
    {
        $this->emitHeaders($response);

        /**
         * Sends headers before the body.
         * Makes a client possible to recognize the type of the body content if it is sent with a delay,
         * for instance, for a streamed response.
         */
        flush();

        $this->emitBody($response);
    }

    private function emitHeaders(ResponseInterface $response): void
    {
        // We can't send headers if they are already sent
        if (headers_sent()) {
            throw new HeadersHaveBeenSentException();
        }

        header_remove();

        $headers = $response->getHeaders();

        // Send headers
        foreach ($headers as $header => $values) {
            foreach ($values as $value) {
                header("$header: $value", false);
            }
        }

        // Send HTTP Status-Line (must be sent after the headers)
        $status = $response->getStatusCode();
        header(
            sprintf(
                'HTTP/%s %d %s',
                $response->getProtocolVersion(),
                $status,
                $response->getReasonPhrase(),
            ),
            true,
            $status
        );
    }

    private function emitBody(ResponseInterface $response): void
    {
        $level = ob_get_level();
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $size = $body->getSize();
        if ($size !== null && $size <= $this->bufferSize) {
            $this->emitContent($body->getContents(), $level);
            return;
        }

        while (!$body->eof()) {
            $this->emitContent($body->read($this->bufferSize), $level);
        }
    }

    private function emitContent(string $content, int $level): void
    {
        if ($content === '') {
            while (ob_get_level() > $level) {
                ob_end_flush();
            }
            return;
        }

        echo $content;

        // flush the output buffer and send echoed messages to the browser
        while (ob_get_level() > $level) {
            ob_end_flush();
        }
        flush();
    }
}
