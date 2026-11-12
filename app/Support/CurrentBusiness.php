<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Membership;

/** Holds the explicitly selected active business for the current dashboard request (P7). */
class CurrentBusiness
{
    public ?Business $business = null;

    public ?Membership $membership = null;

    public function set(Business $business, Membership $membership): void
    {
        $this->business = $business;
        $this->membership = $membership;
    }

    public function get(): Business
    {
        abort_unless($this->business, 403);

        return $this->business;
    }

    public function membership(): Membership
    {
        abort_unless($this->membership, 403);

        return $this->membership;
    }
}
