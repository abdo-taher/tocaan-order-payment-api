<?php

namespace App\DTOs;

readonly class ProcessPaymentDTO
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public int $orderId,
        public float $amount,
        public string $method,
        public array $details = [],
    ) {}

    /**
     * Create from validated request data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            amount: (float) $data['amount'],
            method: $data['method'],
            details: $data['details'] ?? [],
        );
    }

    /**
     * Convert to array.
     *
     * @return array{order_id: int, amount: float, method: string, details: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'method' => $this->method,
            'details' => $this->details,
        ];
    }
}
