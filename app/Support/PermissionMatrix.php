<?php

namespace App\Support;

/**
 * P4 role matrix. "Assigned" staff abilities are additionally checked per record
 * with Membership::canAccessRecord(). Enforced server-side on every request.
 */
class PermissionMatrix
{
    public const ABILITIES = [
        'settings.manage'      => ['owner'],
        'billing.manage'       => ['owner'],
        'team.manage'          => ['owner'],
        'catalog.manage'       => ['owner', 'manager'],
        'catalog.view'         => ['owner', 'manager', 'staff'],
        'hours.manage'         => ['owner', 'manager'],
        'conversations.all'    => ['owner', 'manager'],
        'conversations.reply'  => ['owner', 'manager', 'staff'], // staff: assigned only
        'conversations.assign' => ['owner', 'manager'],
        'transactions.create'  => ['owner', 'manager', 'staff'],
        'transactions.approve' => ['owner', 'manager'],
        'quotes.draft'         => ['owner', 'manager', 'staff'], // staff: assigned only
        'quotes.approve'       => ['owner'],
        'work.complete'        => ['owner', 'manager', 'staff'], // staff: assigned only
        'customers.all'        => ['owner', 'manager'],
        'reports.view'         => ['owner', 'manager'],
        'logs.view'            => ['owner', 'manager'],
        'exports.create'       => ['owner'],
        'payments.verify'      => ['owner', 'manager'],
        'stock.adjust'         => ['owner', 'manager'],
        'business.close'       => ['owner'],
    ];

    public static function allows(string $role, string $ability): bool
    {
        return in_array($role, self::ABILITIES[$ability] ?? [], true);
    }
}
