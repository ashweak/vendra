<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerAccessToken;
use App\Notifications\CustomerAccessLink;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Customers access their own records only through secure verification (P4):
 *  - the browser session that submitted the request, or
 *  - a single-use, expiring link emailed to the address on file.
 * A reference number alone never unlocks private data.
 */
class CustomerAccess
{
    private const SESSION_KEY = 'vendra.customer_access';

    public function grant(Business $business, Customer $customer): void
    {
        $grants = session(self::SESSION_KEY, []);
        $grants[$business->id] = ['customer_id' => $customer->id, 'until' => now()->addHours(2)->timestamp];
        session([self::SESSION_KEY => $grants]);
    }

    public function customer(Business $business): ?Customer
    {
        $g = session(self::SESSION_KEY.'.'.$business->id);
        if (! $g || $g['until'] < now()->timestamp) {
            return null;
        }

        return Customer::forBusiness($business)->find($g['customer_id']);
    }

    public function revoke(Business $business): void
    {
        $grants = session(self::SESSION_KEY, []);
        unset($grants[$business->id]);
        session([self::SESSION_KEY => $grants]);
    }

    /** Always behaves the same whether or not the reference/email match, to avoid leaking existence. */
    public function sendLink(Business $business, string $reference, string $email): void
    {
        $email = strtolower(trim($email));
        $reference = strtoupper(trim($reference));
        $customer = null;
        foreach (['appointments', 'orders', 'quotations', 'serviceRequests'] as $rel) {
            $record = $business->{$rel}()->where('reference', $reference)->with('customer')->first();
            if ($record) {
                $customer = $record->customer;
                break;
            }
        }
        if (! $customer || ! $customer->email || ! hash_equals($customer->email, $email)) {
            return;
        }

        $token = Str::random(48);
        $t = new CustomerAccessToken([
            'customer_id' => $customer->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
        ]);
        $t->business_id = $business->id;
        $t->save();

        Notification::route('mail', $customer->email)
            ->notify(new CustomerAccessLink($business, route('public.access', [$business, $token])));
    }

    public function consume(Business $business, string $token): ?Customer
    {
        $t = CustomerAccessToken::forBusiness($business)
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
        if (! $t) {
            return null;
        }
        $t->update(['used_at' => now()]);
        $this->grant($business, $t->customer);

        return $t->customer;
    }
}
