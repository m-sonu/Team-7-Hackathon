<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class LlmRequestFailedException extends Exception
{
    /**
     * Create a new exception instance for a failed LLM request.
     *
     * @param  string  $message  The exception message.
     * @param  int  $code  The exception code.
     * @param  Throwable|null  $previous  The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a new exception for a failed request from a specific provider.
     */
    public static function forProvider(string $providerName, string $responseBody, int $statusCode): self
    {
        $message = "The {$providerName} API request failed with status code {$statusCode}. Response body: {$responseBody}";

        // Use the status code as the exception code for clarity
        return new static($message, $statusCode);
    }

    /**
     * Report the exception (optional, Laravel handles this by default).
     * You can add custom logging or monitoring logic here.
     */
    public function report()
    {
        // Example: Log the error using Laravel's logging system
        Log::error('LLM Request Failed', [
            'provider' => $this->getProviderName(),
            'status' => $this->getCode(),
            'message' => $this->getMessage(),
        ]);
    }

    /**
     * Try to extract the provider name from the message.
     */
    protected function getProviderName(): string
    {
        if (preg_match('/The (.*?) API request failed/', $this->getMessage(), $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }
}
