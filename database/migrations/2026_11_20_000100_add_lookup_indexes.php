<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the lookups the dashboard runs most: list pages sorted by recent activity,
 * a customer's history, a conversation's messages and a record's audit trail.
 * SQLite and PostgreSQL don't index foreign keys on their own, so these also stop full scans there.
 */
return new class extends Migration
{
    private array $indexes = [
        'customers' => [['business_id', 'last_activity_at']],
        'conversations' => [['business_id', 'last_activity_at']],
        'messages' => [['conversation_id', 'created_at']],
        'appointments' => [['customer_id', 'starts_at']],
        'orders' => [['customer_id', 'created_at']],
        'order_items' => [['order_id', 'catalog_item_id']],
        'quotations' => [['customer_id', 'created_at']],
        'service_requests' => [['customer_id', 'created_at']],
        'audit_logs' => [['business_id', 'subject_type', 'subject_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $sets) {
            Schema::table($table, function (Blueprint $t) use ($table, $sets) {
                foreach ($sets as $columns) {
                    $t->index($columns, $this->name($table, $columns));
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $sets) {
            Schema::table($table, function (Blueprint $t) use ($table, $sets) {
                foreach ($sets as $columns) {
                    $t->dropIndex($this->name($table, $columns));
                }
            });
        }
    }

    private function name(string $table, array $columns): string
    {
        return $table.'_'.implode('_', $columns).'_lookup';
    }
};
