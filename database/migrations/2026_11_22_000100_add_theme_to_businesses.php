<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The website theme a business picked for its customer pages (see App\Support\Themes). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $t) {
            $t->string('theme', 30)->default('studio')->after('accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $t) {
            $t->dropColumn('theme');
        });
    }
};
