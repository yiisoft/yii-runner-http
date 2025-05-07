<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;

use function flush;
use function headers_sent;
use function in_array;
use function sprintf;

/**
 * `SapiEmitter` sends a response using standard PHP Server API, i.e., with {@see header()} and "echo".
 */
final class SapiEmitter implements EmitterInterface
{
    private const NO_BODY_RESPONSE_CODES = [
        Status::CONTINUE,
        Status::SWITCHING_PROTOCOLS,
        Status::PROCESSING,
        Status::NO_CONTENT,
        Status::RESET_CONTENT,
        Status::NOT_MODIFIED,
    ];

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
        $level = ob_get_level();

        if (!$this->shouldOutputBody($response)) {
            $response = $response->withoutHeader(Header::CONTENT_LENGTH);
            $this->emitHeaders($response);
            return;
        }

        // Adds a `Content-Length` header if a body exists, and it has not been added before
        if (!$response->hasHeader(Header::TRANSFER_ENCODING) && !$response->hasHeader(Header::CONTENT_LENGTH)) {
            $contentLength = $response->getBody()->getSize();
            if ($contentLength !== null) {
                $response = $response->withHeader(Header::CONTENT_LENGTH, (string) $contentLength);
            }
        }

        $this->emitHeaders($response);

        /**
         * Sends headers before the body.
         * Makes a client possible to recognize the type of the body content if it is sent with a delay,
         * for instance, for a streamed response.
         */
        flush();

        $this->emitBody($response, $level);
    }

    public function emitHeaders(ResponseInterface $response): void
    {
        // We can't send headers if they are already sent
        if (headers_sent()) {
            throw new HeadersHaveBeenSentException();
        }

        header_remove();

        $headers = $response->getHeaders();
        if (isset($headers[Header::TRANSFER_ENCODING])) {
            unset($headers[Header::CONTENT_LENGTH]);
        }

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

    private function emitBody(ResponseInterface $response, int $level): void
    {
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

    private function shouldOutputBody(ResponseInterface $response): bool
    {
        if (in_array($response->getStatusCode(), self::NO_BODY_RESPONSE_CODES, true)) {
            return false;
        }

        $body = $response->getBody();

        if (!$body->isReadable()) {
            return false;
        }

        $size = $body->getSize();

        if ($size !== null) {
            return $size > 0;
        }

        if ($body->isSeekable()) {
            $body->rewind();
            $byte = $body->read(1);

            if ($byte === '' || $body->eof()) {
                return false;
            }
        }

        return true;
    }
}
