<?php

namespace App\Services\Notification\DTOs;

class NotificationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $reference = null,
        public readonly ?string $error = null,
        public readonly array $response = []
    ) {}

    /**
     * Create a successful result
     */
    public static function success(?string $reference = null, array $response = []): self
    {
        return new self(
            success: true,
            reference: $reference,
            error: null,
            response: $response
        );
    }

    /**
     * Create a failed result
     */
    public static function failure(string $error, array $response = []): self
    {
        return new self(
            success: false,
            reference: null,
            error: $error,
            response: $response
        );
    }

    /**
     * Create from MsgClub provider response
     */
    public static function fromProviderResponse(array $providerResponse): self
    {
        return new self(
            success: $providerResponse['success'],
            reference: $providerResponse['reference'],
            error: $providerResponse['error'],
            response: $providerResponse['response']
        );
    }
}
