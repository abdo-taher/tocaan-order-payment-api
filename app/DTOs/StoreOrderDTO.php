<?php

namespace App\DTOs;

readonly class StoreOrderDTO
{
    /**
     * @param array<int, array{product_name: string, quantity: int, price: float}> $items
     */
    public function __construct(
        public int $userId,
        public array $items,
    ) {}

    /**
     * Create from validated request data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            items: $data['items'],
        );
    }

    /**
     * Convert to array.
     *
     * @return array{user_id: int, items: array<int, array{product_name: string, quantity: int, price: float}>}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'items' => $this->items,
        ];
    }
}
