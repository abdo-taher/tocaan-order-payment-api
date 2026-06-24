<?php

namespace App\DTOs;

readonly class UpdateOrderDTO
{
    /**
     * @param array<int, array{product_name: string, quantity: int, price: float}>|null $items
     */
    public function __construct(
        public ?array $items = null,
    ) {}

    /**
     * Create from validated request data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: $data['items'] ?? null,
        );
    }

    /**
     * Convert to array.
     *
     * @return array{items: array<int, array{product_name: string, quantity: int, price: float}>|null}
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
        ];
    }
}
