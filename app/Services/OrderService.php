<?php

namespace App\Services;

use App\Exceptions\RuleViolation;
use App\Models\Business;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Product orders (P5). Carts never reserve stock; submitted orders reserve it until
 * handover (deduct) or cancellation/expiry (release). Never double-deducts.
 */
class OrderService
{
    public const TRANSITIONS = [
        'pending_approval' => ['confirmed', 'declined', 'cancelled', 'expired'],
        'confirmed' => ['preparing', 'ready_for_pickup', 'dispatched', 'cancelled', 'expired'],
        'preparing' => ['ready_for_pickup', 'dispatched', 'cancelled'],
        'ready_for_pickup' => ['completed', 'cancelled', 'expired'],
        'dispatched' => ['completed'],
    ];

    /**
     * Price a cart from live catalog data. $items: [catalog_item_id => quantity].
     * Returns ['lines' => [...], 'subtotal_cents' => int, 'delivery_fee_cents' => ?int, 'total_cents' => int].
     */
    public function price(Business $business, array $items, string $method): array
    {
        $items = array_filter(array_map('intval', $items), fn ($q) => $q > 0);
        if (! $items) {
            throw new RuleViolation('Please choose at least one product.');
        }
        if (! in_array($method, (array) $business->setting('fulfillment_methods'), true)) {
            throw new RuleViolation('That fulfillment method is not offered by this business.');
        }

        $products = CatalogItem::forBusiness($business)->products()->active()->whereIn('id', array_keys($items))->get()->keyBy('id');
        $lines = [];
        $subtotal = 0;
        foreach ($items as $id => $qty) {
            $p = $products->get($id);
            if (! $p) {
                throw new RuleViolation('One of the selected products is no longer available.');
            }
            $available = $p->availableStock();
            if ($available !== null && $qty > $available) {
                throw new RuleViolation("Only {$available} left of {$p->name}.", 'insufficient_stock', ['item' => $p->id, 'available' => $available]);
            }
            $line = $p->price_cents * $qty;
            $subtotal += $line;
            $lines[] = ['catalog_item_id' => $p->id, 'name' => $p->name, 'unit_price_cents' => $p->price_cents, 'quantity' => $qty, 'line_total_cents' => $line];
        }

        // Delivery charges are unknown until staff review (P5).
        $fee = $method === 'delivery' ? null : 0;

        return ['lines' => $lines, 'subtotal_cents' => $subtotal, 'delivery_fee_cents' => $fee, 'total_cents' => $subtotal + (int) $fee];
    }

    /**
     * Submit an order. $reviewedSubtotal is what the customer saw on the review page; if live prices
     * changed since, the customer must reconfirm (RuleViolation code 'price_changed').
     */
    public function place(Business $business, Customer $customer, array $items, string $method, ?string $address,
        ?int $reviewedSubtotal, ?string $idempotencyKey, ?User $actor = null, ?string $notes = null): Order
    {
        abort_unless((int) $customer->business_id === (int) $business->id, 404);

        if ($idempotencyKey && $existing = Order::forBusiness($business)->where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }
        if ($method === 'delivery' && ! trim((string) $address)) {
            throw new RuleViolation('Please provide a delivery address.');
        }

        return DB::transaction(function () use ($business, $customer, $items, $method, $address, $reviewedSubtotal, $idempotencyKey, $actor, $notes) {
            // Lock product rows in id order to avoid deadlocks and overselling.
            CatalogItem::forBusiness($business)->whereIn('id', array_keys($items))->orderBy('id')->lockForUpdate()->get();
            $priced = $this->price($business, $items, $method);

            if ($reviewedSubtotal !== null && $reviewedSubtotal !== $priced['subtotal_cents']) {
                throw new RuleViolation('Prices changed since you reviewed your order. Please review the updated total.', 'price_changed');
            }

            $manual = $business->setting('order_approval') === 'manual' || $priced['delivery_fee_cents'] === null;

            $order = new Order([
                'customer_id' => $customer->id,
                'status' => $manual ? 'pending_approval' : 'confirmed',
                'fulfillment_method' => $method,
                'delivery_address' => $method === 'delivery' ? $address : null,
                'delivery_fee_cents' => $priced['delivery_fee_cents'],
                'subtotal_cents' => $priced['subtotal_cents'],
                'total_cents' => $priced['total_cents'],
                'reservation_expires_at' => now()->addHours((int) $business->setting('order_reservation_hours')),
                'notes' => $notes,
                'created_by' => $actor?->id,
                'assigned_to' => $actor?->id,
                'idempotency_key' => $idempotencyKey,
            ]);
            $order->business_id = $business->id;
            $order->save();

            foreach ($priced['lines'] as $line) {
                $order->items()->create($line);
                $this->moveStock($order, $line['catalog_item_id'], 'reserve', $line['quantity'], $actor);
            }

            AuditLogger::log($business->id, 'order.created', $order, ['status' => $order->status, 'total_cents' => $order->total_cents],
                actorType: $actor ? 'user' : 'customer', actorId: $actor?->id ?? $customer->id);

            return $order;
        });
    }

    public function transition(Order $order, string $to, ?User $actor = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $reason) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($to, self::TRANSITIONS[$o->status] ?? [], true)) {
                throw new RuleViolation("A {$o->statusLabel()} order cannot be changed to ".(Order::STATUSES[$to] ?? $to).'.');
            }
            if ($to === 'confirmed' && $o->delivery_fee_cents === null) {
                throw new RuleViolation('Set the delivery fee before confirming this order.');
            }
            if (in_array($to, ['cancelled', 'declined', 'expired'], true)) {
                if ($o->stock_deducted) {
                    throw new RuleViolation('This order was already handed over. Use the return/exception process instead.');
                }
                $this->releaseAll($o, $actor);
            }
            // Deduct stock at handover: dispatch (delivery) or completion of a pickup.
            if (in_array($to, ['dispatched', 'completed'], true) && ! $o->stock_deducted) {
                foreach ($o->items as $item) {
                    if ($item->catalog_item_id) {
                        $this->moveStock($o, $item->catalog_item_id, 'deduct', $item->quantity, $actor);
                    }
                }
                $o->stock_deducted = true;
            }

            $from = $o->status;
            $o->status = $to;
            $o->status_reason = $reason;
            $o->save();
            AuditLogger::log($o->business_id, "order.$to", $o, ['status' => [$from, $to]], $reason, $actor ? 'user' : 'system', $actor?->id);

            return $o;
        });
    }

    public function setDeliveryFee(Order $order, int $feeCents, User $actor): Order
    {
        if ($order->status !== 'pending_approval') {
            throw new RuleViolation('The delivery fee can only be set while the order is pending approval.');
        }
        $old = $order->delivery_fee_cents;
        $order->delivery_fee_cents = $feeCents;
        $order->total_cents = $order->subtotal_cents + $feeCents;
        $order->save();
        AuditLogger::log($order->business_id, 'order.delivery_fee_set', $order, ['delivery_fee_cents' => [$old, $feeCents]]);

        return $order;
    }

    /** Only owners/managers verify payments – never the AI, never from a message or screenshot. */
    public function verifyPayment(Order $order, User $actor, string $reason): Order
    {
        $order->payment_status = 'verified';
        $order->payment_verified_by = $actor->id;
        $order->save();
        AuditLogger::log($order->business_id, 'order.payment_verified', $order, ['payment_status' => 'verified'], $reason);

        return $order;
    }

    public function adjustStock(CatalogItem $item, int $delta, User $actor, string $reason): CatalogItem
    {
        return DB::transaction(function () use ($item, $delta, $actor, $reason) {
            $p = CatalogItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $new = (int) $p->stock_on_hand + $delta;
            if ($new < $p->stock_reserved) {
                throw new RuleViolation("Stock cannot go below the {$p->stock_reserved} units reserved for open orders.");
            }
            $p->stock_on_hand = $new;
            $p->save();
            $m = new StockMovement(['catalog_item_id' => $p->id, 'type' => 'adjust', 'quantity' => $delta, 'user_id' => $actor->id, 'reason' => $reason]);
            $m->business_id = $p->business_id;
            $m->save();
            AuditLogger::log($p->business_id, 'stock.adjusted', $p, ['delta' => $delta, 'stock_on_hand' => $new], $reason);

            return $p;
        });
    }

    public function expireOverdue(): int
    {
        $n = 0;
        Order::whereIn('status', ['pending_approval', 'confirmed', 'ready_for_pickup'])
            ->where('stock_deducted', false)
            ->where('reservation_expires_at', '<', now())
            ->each(function (Order $o) use (&$n) {
                $this->transition($o, 'expired', null, 'Reservation deadline passed');
                $n++;
            });

        return $n;
    }

    private function releaseAll(Order $order, ?User $actor): void
    {
        foreach ($order->items as $item) {
            if ($item->catalog_item_id) {
                $this->moveStock($order, $item->catalog_item_id, 'release', $item->quantity, $actor);
            }
        }
    }

    private function moveStock(Order $order, int $itemId, string $type, int $qty, ?User $actor): void
    {
        $p = CatalogItem::whereKey($itemId)->lockForUpdate()->first();
        if (! $p || $p->stock_on_hand === null) {
            return; // untracked stock
        }
        match ($type) {
            'reserve' => $p->stock_reserved += $qty,
            'release' => $p->stock_reserved = max(0, $p->stock_reserved - $qty),
            'deduct' => [$p->stock_reserved = max(0, $p->stock_reserved - $qty), $p->stock_on_hand -= $qty],
        };
        $p->save();
        $m = new StockMovement(['catalog_item_id' => $p->id, 'order_id' => $order->id, 'type' => $type, 'quantity' => $qty, 'user_id' => $actor?->id]);
        $m->business_id = $order->business_id;
        $m->save();
    }
}
