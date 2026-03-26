<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by MenuExtractionService when AI menu extraction fails.
 *
 * The $reason codes map directly to lang keys: snap_{reason}
 * Supported reasons: api_key, rate_limit, timeout, invalid_image, api_error, parse_error
 */
class MenuExtractionException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?: $reason, 0, $previous);
    }
}
