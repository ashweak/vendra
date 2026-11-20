<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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

        Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
        $this->assertFalse(Schema::hasIndex('messages', ['conversation_id', 'created_at']));

        Artisan::call('migrate', ['--force' => true]);
        $this->assertTrue(Schema::hasIndex('messages', ['conversation_id', 'created_at']));
    }
}
