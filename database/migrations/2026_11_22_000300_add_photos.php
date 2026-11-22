<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pictures: business logo, cover and gallery; catalog item photos; profile pictures. Files live on the public disk. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $t) {
            $t->string('logo_path')->nullable()->after('palette');
            $t->string('cover_path')->nullable()->after('logo_path');
        });
        Schema::table('catalog_items', function (Blueprint $t) {
            $t->string('image_path')->nullable()->after('description');
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('avatar_path')->nullable()->after('email');
        });
        Schema::create('business_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('caption', 120)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->index(['business_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_photos');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('avatar_path'));
        Schema::table('catalog_items', fn (Blueprint $t) => $t->dropColumn('image_path'));
        Schema::table('businesses', fn (Blueprint $t) => $t->dropColumn(['logo_path', 'cover_path']));
    }
};
