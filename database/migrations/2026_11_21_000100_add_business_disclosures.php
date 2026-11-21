<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Details a business shows customers on its page (Internet Transactions Act, RA 11967) and its
 * privacy contact (Data Privacy Act, RA 10173).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $t) {
            $t->string('legal_name')->nullable()->after('name');        // registered business / corporate name
            $t->string('registration_no', 60)->nullable()->after('legal_name'); // DTI or SEC registration number
            $t->string('privacy_contact')->nullable()->after('address'); // who handles privacy requests (email)
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $t) {
            $t->dropColumn(['legal_name', 'registration_no', 'privacy_contact']);
        });
    }
};
