<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Data subject requests (Data Privacy Act, RA 10173): correction or deletion of a customer's details. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');                 // correction | deletion
            $t->text('details')->nullable();
            $t->string('status')->default('open'); // open | done | declined
            $t->string('resolution')->nullable();
            $t->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('handled_at')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
    }
};
