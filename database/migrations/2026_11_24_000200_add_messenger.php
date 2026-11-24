<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('messenger_page_id', 40)->nullable()->unique()->after('palette');
            $table->text('messenger_token')->nullable()->after('messenger_page_id'); // encrypted at rest
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('external_id', 80)->nullable()->after('channel'); // Messenger sender (page-scoped ID)
            $table->index(['business_id', 'channel', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'channel', 'external_id']);
            $table->dropColumn('external_id');
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique(['messenger_page_id']);
            $table->dropColumn(['messenger_page_id', 'messenger_token']);
        });
    }
};
