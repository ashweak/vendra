<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Questions the auto-assistant couldn't answer, grouped by wording, so the owner can add answers.
        Schema::create('assistant_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question', 300);
            $table->string('fingerprint', 64);
            $table->unsignedInteger('times_asked')->default(1);
            $table->timestamp('last_asked_at');
            $table->string('status', 20)->default('open'); // open | answered | dismissed
            $table->foreignId('knowledge_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['business_id', 'fingerprint']);
            $table->index(['business_id', 'status', 'last_asked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_gaps');
    }
};
