<?php

return [
    // Owners and platform admins must use TOTP MFA (P7). Only disable for local fictional demos.
    'enforce_mfa' => env('VENDRA_ENFORCE_MFA', true),

    // Public demo deployments: fictional data that resets on every restart; demo logins are shown.
    'public_demo' => (bool) env('VENDRA_PUBLIC_DEMO', false),
    // Shown on the platform privacy notice and terms. Leave empty to point people to the GitHub repository.
    'contact_email' => env('VENDRA_CONTACT_EMAIL'),

    // Where uploads live. 'public'/'local' keep files on the server; on hosts whose disk is wiped on
    // restart (Render free plan), use Cloudflare R2: VENDRA_MEDIA_DISK=r2 and VENDRA_FILES_DISK=r2_private.
    'media_disk' => env('VENDRA_MEDIA_DISK', 'public'),   // business photos, product photos, profile pictures
    'files_disk' => env('VENDRA_FILES_DISK', 'local'),    // private customer attachments (quotation files)

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
        'booking_reminders' => true,
        'language' => 'en',                    // default customer page language: en | fil (visitors can switch)           // email customers the day before (with a calendar file)
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
        'unanswered_questions_days' => 90,     // assistant gaps not asked again within this time are removed
    ],

    'ai' => [
        // The auto-assistant is the offline rules engine only: no third-party AI service and no API key.
        'default_monthly_limit' => 300,
        'warn_at_percent' => 80,
        'history_messages' => 12,
    ],
];
