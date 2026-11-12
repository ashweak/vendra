<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_token_hash', 64)->unique();
            $table->string('state')->default('ai_active'); // ai_active | waiting_for_staff | staff_active | closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('handoff_summary')->nullable();
            $table->string('channel')->default('web');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'state']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type'); // customer | ai | staff | system
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 20)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_name');
            $table->unsignedInteger('price_cents');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status'); // pending_approval | confirmed | completed | cancelled | declined | expired | no_show
            $table->string('source')->default('online'); // online | walk_in | phone
            $table->dateTime('approval_deadline_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('status_reason')->nullable();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'idempotency_key']);
            $table->index(['business_id', 'staff_id', 'starts_at']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 20)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // pending_approval | confirmed | preparing | ready_for_pickup | dispatched | completed | cancelled | declined | expired
            $table->string('payment_status')->default('unpaid'); // unpaid | verified
            $table->string('fulfillment_method'); // pickup | delivery
            $table->text('delivery_address')->nullable();
            $table->unsignedInteger('delivery_fee_cents')->nullable(); // null = needs review
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('total_cents');
            $table->dateTime('reservation_expires_at')->nullable();
            $table->boolean('stock_deducted')->default(false);
            $table->text('notes')->nullable();
            $table->string('status_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'idempotency_key']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total_cents');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // reserve | release | deduct | adjust
            $table->integer('quantity');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 20)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // submitted | under_review | awaiting_owner_approval | sent | accepted | declined | expired | withdrawn
            $table->text('requirements');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedBigInteger('accepted_version_id')->nullable();
            $table->string('converted_type')->nullable();
            $table->unsignedBigInteger('converted_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'idempotency_key']);
        });

        Schema::create('quotation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->json('line_items');
            $table->unsignedInteger('total_cents');
            $table->text('notes')->nullable();
            $table->date('valid_until');
            $table->foreignId('drafted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['quotation_id', 'version']);
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 20)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // submitted | under_review | awaiting_customer | accepted | in_progress | completed | declined | cancelled
            $table->text('description');
            $table->string('location')->nullable();
            $table->date('preferred_date')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'idempotency_key']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->morphs('attachable');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['attachments', 'service_requests', 'quotation_versions', 'quotations', 'stock_movements',
            'order_items', 'orders', 'appointments', 'messages', 'conversations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
