<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(3)->create();

        foreach ($users as $user) {
            // Create a pending order with items
            $pendingOrder = Order::factory()
                ->for($user)
                ->create(['status' => OrderStatus::Pending]);

            OrderItem::factory(3)->for($pendingOrder)->create();
            $pendingOrder->recalculateTotal();

            // Create a confirmed order with items and successful payment
            $confirmedOrder = Order::factory()
                ->for($user)
                ->confirmed()
                ->create();

            OrderItem::factory(2)->for($confirmedOrder)->create();
            $confirmedOrder->recalculateTotal();

            Payment::factory()
                ->successful()
                ->create([
                    'order_id' => $confirmedOrder->id,
                    'amount' => $confirmedOrder->total,
                    'method' => PaymentMethod::CreditCard,
                ]);

            // Create a cancelled order
            $cancelledOrder = Order::factory()
                ->for($user)
                ->cancelled()
                ->create();

            OrderItem::factory(1)->for($cancelledOrder)->create();
            $cancelledOrder->recalculateTotal();
        }
    }
}
