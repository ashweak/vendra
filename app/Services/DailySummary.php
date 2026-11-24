<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AssistantGap;
use App\Models\Business;
use App\Models\CatalogItem;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Quotation;
use Carbon\CarbonImmutable;

/** What an owner needs to know at the start of the day, from live records only. */
class DailySummary
{
    public static function for(Business $b, ?CarbonImmutable $day = null): array
    {
        $day ??= CarbonImmutable::now($b->timezone);
        $start = $day->startOfDay()->utc();
        $end = $day->endOfDay()->utc();

        $bookings = Appointment::forBusiness($b)->whereIn('status', ['confirmed', 'pending_approval'])
            ->whereBetween('starts_at', [$start, $end])->with('customer')->orderBy('starts_at')->get();

        return [
            'date' => local_time($day, $b->timezone, 'l, F j'),
            'bookings' => $bookings->map(fn ($a) => local_time($a->starts_at, $b->timezone, 'g:i A').' · '.$a->service_name
                .($a->customer?->name ? ' · '.$a->customer->name : '').($a->status === 'pending_approval' ? ' (needs approval)' : ''))->all(),
            'approvals' => Appointment::forBusiness($b)->where('status', 'pending_approval')->count()
                + Order::forBusiness($b)->where('status', 'pending_approval')->count()
                + Quotation::forBusiness($b)->where('status', 'awaiting_owner_approval')->count(),
            'orders_to_prepare' => Order::forBusiness($b)->whereIn('status', ['confirmed', 'preparing'])->count(),
            'low_stock' => CatalogItem::forBusiness($b)->active()->products()->whereNotNull('stock_on_hand')->get()
                ->filter(fn ($i) => $i->availableStock() <= 3)
                ->map(fn ($i) => $i->name.' ('.$i->availableStock().' left)')->values()->all(),
            'chats_waiting' => Conversation::forBusiness($b)->where('state', 'waiting_for_staff')->count(),
            'unanswered' => AssistantGap::forBusiness($b)->where('status', 'open')->count(),
        ];
    }

    public static function isEmpty(array $s): bool
    {
        return ! $s['bookings'] && ! $s['approvals'] && ! $s['orders_to_prepare'] && ! $s['low_stock'] && ! $s['chats_waiting'] && ! $s['unanswered'];
    }
}
