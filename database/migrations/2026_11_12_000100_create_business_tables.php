<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('general');
            $table->string('timezone')->default('Asia/Manila');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('accent_color', 7)->default('#4f46e5');
            $table->string('greeting')->nullable();
            $table->string('ai_tone')->default('friendly');
            $table->boolean('ai_enabled')->default(true);
            $table->json('modules');
            $table->json('settings');
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->timestamp('activated_at')->nullable();
            $table->string('status')->default('active'); // active | suspended | closing
            $table->timestamp('closing_requested_at')->nullable();
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // owner | manager | staff
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'user_id']);
        });

        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = Sunday
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->unique(['business_id', 'weekday']);
        });

        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->index(['business_id', 'user_id', 'weekday']);
        });

        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // service | product
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->integer('stock_on_hand')->nullable();
            $table->integer('stock_reserved')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['business_id', 'type', 'is_active']);
        });

        Schema::create('catalog_item_user', function (Blueprint $table) {
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['catalog_item_id', 'user_id']);
        });

        Schema::create('knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('kind'); // faq | policy
            $table->string('title');
            $table->text('body');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'email']);
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // marketing | data_processing
            $table->boolean('granted');
            $table->string('source');
            $table->timestamp('recorded_at');
        });

        Schema::create('customer_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['customer_access_tokens', 'consents', 'customers', 'knowledge_entries', 'catalog_item_user',
            'catalog_items', 'staff_schedules', 'business_hours', 'memberships', 'businesses'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
