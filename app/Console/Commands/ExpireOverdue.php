<?php

namespace App\Console\Commands;

use App\Services\AppointmentService;
use App\Services\OrderService;
use App\Services\QuotationService;
use Illuminate\Console\Command;

class ExpireOverdue extends Command
{
    protected $signature = 'vendra:expire';

    protected $description = 'Expire overdue pending bookings, order reservations and quotations';

    public function handle(AppointmentService $a, OrderService $o, QuotationService $q): int
    {
        $this->info(sprintf('Expired: %d bookings, %d orders, %d quotations', $a->expireOverdue(), $o->expireOverdue(), $q->expireOverdue()));

        return self::SUCCESS;
    }
}
