<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta;

use Exception;
use Throwable;

/**
 * Class UkrposhtaApiException
 *
 * The exception code is the HTTP status code of the failed response (when available),
 * while the Ukrposhta-specific error code (e.g. `UPE02001`) is available via getApiErrorCode().
 *
 * @see https://www.ukrposhta.ua/ecom/0.0.1/dictionaries/error-codes
 */
class UkrposhtaApiException extends Exception
{
    private ?string $apiErrorCode;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string|null $apiErrorCode
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $apiErrorCode = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->apiErrorCode = $apiErrorCode;
    }

    /**
     * @return string|null
     */
    public function getApiErrorCode(): ?string
    {
        return $this->apiErrorCode;
    }
}
