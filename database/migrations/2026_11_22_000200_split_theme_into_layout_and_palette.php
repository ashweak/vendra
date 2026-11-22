<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A business now picks its page layout and its color palette separately (see App\Support\Appearance).
 * Existing businesses keep exactly the look they had: each old theme maps to its layout and palette.
 */
return new class extends Migration
{
    private const MAP = [
        'studio' => ['classic', 'sunny'], 'coral' => ['brochure', 'coral'], 'ocean' => ['spotlight', 'ocean'],
        'roastery' => ['cafe', 'espresso'], 'slate' => ['panel', 'slate'], 'market' => ['shop', 'garden'],
    ];

    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $t) {
            $t->string('layout', 30)->default('classic')->after('accent_color');
            $t->string('palette', 30)->default('sunny')->after('layout');
        });
        foreach (self::MAP as $old => [$layout, $palette]) {
            DB::table('businesses')->where('theme', $old)->update(['layout' => $layout, 'palette' => $palette]);
        }
        Schema::table('businesses', function (Blueprint $t) {
            $t->dropColumn('theme');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $t) {
            $t->string('theme', 30)->default('studio')->after('accent_color');
        });
        foreach (self::MAP as $old => [$layout]) {
            DB::table('businesses')->where('layout', $layout)->update(['theme' => $old]);
        }
        Schema::table('businesses', function (Blueprint $t) {
            $t->dropColumn(['layout', 'palette']);
        });
    }
};
