<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_indexes_exist_and_roll_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasIndex('messages', ['conversation_id', 'created_at']));
        $this->assertTrue(Schema::hasIndex('customers', ['business_id', 'last_activity_at']));
        $this->assertTrue(Schema::hasIndex('audit_logs', ['business_id', 'subject_type', 'subject_id']));

        $migration = require database_path('migrations/2026_11_20_000100_add_lookup_indexes.php');
        $migration->down();
        $this->assertFalse(Schema::hasIndex('messages', ['conversation_id', 'created_at']));

        $migration->up();
        $this->assertTrue(Schema::hasIndex('messages', ['conversation_id', 'created_at']));
    }
}
