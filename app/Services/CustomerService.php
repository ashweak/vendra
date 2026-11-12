<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Consent;
use App\Models\Customer;

class CustomerService
{
    /**
     * Find or create the business-specific customer record. Matching by email is internal
     * record-keeping only; it never grants the requester access to history (P6/P7).
     */
    public function resolve(Business $business, array $data, string $source = 'web_form'): Customer
    {
        $email = isset($data['email']) ? strtolower(trim($data['email'])) : null;

        $customer = $email
            ? Customer::forBusiness($business)->where('email', $email)->first()
            : null;

        if (! $customer) {
            $customer = new Customer(['name' => $data['name'], 'email' => $email, 'phone' => $data['phone'] ?? null]);
            $customer->business_id = $business->id;
        } else {
            $customer->name = $data['name'] ?: $customer->name;
            $customer->phone = ($data['phone'] ?? null) ?: $customer->phone;
        }

        $customer->last_activity_at = now();
        $customer->save();

        if (array_key_exists('marketing_consent', $data)) {
            $granted = (bool) $data['marketing_consent'];
            if ($granted !== $customer->marketing_consent || ! $customer->consents()->exists()) {
                $customer->marketing_consent = $granted;
                $customer->save();
                Consent::create([
                    'business_id' => $business->id,
                    'customer_id' => $customer->id,
                    'type' => 'marketing',
                    'granted' => $granted,
                    'source' => $source,
                    'recorded_at' => now(),
                ]);
            }
        }

        return $customer;
    }
}
