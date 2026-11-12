<?php

return [
    // Owners and platform admins must use TOTP MFA (P7). Only disable for local fictional demos.
    'enforce_mfa' => env('VENDRA_ENFORCE_MFA', true),

    'modules' => [
        'appointments' => 'Appointments',
        'quotations' => 'Quotations',
        'service_requests' => 'Service requests',
        'orders' => 'Product orders',
    ],

    'categories' => [
        'salon' => ['label' => 'Salon / beauty services', 'modules' => ['appointments', 'quotations']],
        'tutorial' => ['label' => 'Tutorial / lessons', 'modules' => ['appointments']],
        'retail' => ['label' => 'Retail shop', 'modules' => ['orders']],
        'cleaning' => ['label' => 'Cleaning / home services', 'modules' => ['service_requests', 'quotations', 'appointments']],
        'general' => ['label' => 'Other small business', 'modules' => ['appointments']],
    ],

    'default_settings' => [
        'booking_approval' => 'auto',          // auto | manual
        'pending_deadline_hours' => 12,        // P5: pending approval deadline shown to customer
        'slot_interval_minutes' => 30,
        'booking_lead_minutes' => 60,          // earliest bookable time from now
        'booking_horizon_days' => 30,
        'cancellation_hours' => 6,             // self-service cancellation window
        'order_approval' => 'manual',          // auto | manual
        'order_reservation_hours' => 24,       // reservation expiry for submitted orders
        'fulfillment_methods' => ['pickup'],   // pickup, delivery
        'quote_valid_days' => 14,
        'response_hours' => 'Mon–Sat, 9:00 AM – 6:00 PM',
    ],

    // Provisional retention (P7) – product settings, not legal retention periods.
    'retention' => [
        'conversations_months' => 12,
        'attachments_days' => 90,
        'ai_diagnostics_days' => 30,
        'audit_logs_months' => 12,
        'access_tokens_days' => 7,
    ],

    'ai' => [
        'provider' => env('VENDRA_AI_PROVIDER', 'rules'), // gemini | rules
        'gemini_key' => env('GEMINI_API_KEY'),
        // Check current model availability and free-tier limits at M8; do not assume.
        'gemini_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout_seconds' => 20,
        'max_tool_rounds' => 4,
        'default_monthly_limit' => 300,
        'warn_at_percent' => 80,
        'history_messages' => 12,
    ],
];
