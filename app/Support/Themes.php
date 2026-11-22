<?php

namespace App\Support;

/**
 * Website themes a business can choose for its customer pages. Each theme only swaps design tokens
 * (colors, headline font, corner radius) defined in resources/css/app.css under [data-theme="…"],
 * so every page keeps the same layout, accessibility and behaviour.
 */
class Themes
{
    public const DEFAULT = 'studio';

    public const ALL = [
        'studio' => ['name' => 'Studio', 'description' => 'Split welcome with a chat preview and a classic two-column price list. Warm paper and sunny yellow.', 'font' => 'Bricolage Grotesque', 'swatches' => ['#fbfaf6', '#ffc83d', '#171612']],
        'coral' => ['name' => 'Coral', 'description' => 'Centered welcome, quick-action cards and service cards. Soft peach, coral buttons, serif headlines.', 'font' => 'Fraunces (serif)', 'swatches' => ['#fff8f2', '#ff7043', '#242b38']],
        'ocean' => ['name' => 'Ocean', 'description' => 'Bold headline with stats, a swipeable item row and a ring-style how-it-works. Cool blue and navy.', 'font' => 'Inter', 'swatches' => ['#f4f7ff', '#46c0ff', '#14205e']],
        'roastery' => ['name' => 'Roastery', 'description' => 'Bento welcome with a featured item, scrolling categories and menu cards. Cream, espresso and burnt orange.', 'font' => 'Fraunces (serif)', 'swatches' => ['#f7f0e7', '#e0703a', '#2d1d16']],
        'slate' => ['name' => 'Slate', 'description' => 'App-style panel: business card on the side, stat cards and a clean price table. White, slate and teal.', 'font' => 'Inter', 'swatches' => ['#f8fafc', '#22b3cf', '#16202f']],
        'market' => ['name' => 'Market', 'description' => 'Store-style banner with search, a category shelf and a product grid. Fresh greens and lime.', 'font' => 'Bricolage Grotesque', 'swatches' => ['#f7faf4', '#a9dc5e', '#133d30']],
    ];

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    /**
     * The theme to render for a business page: a valid ?theme= preview (it only changes what that visitor
     * sees), otherwise the business's saved theme.
     */
    public static function active(\App\Models\Business $business): string
    {
        $preview = request()->query('theme');

        return is_string($preview) && isset(self::ALL[$preview]) ? $preview : self::resolve($business->theme);
    }

    /** A valid theme key, falling back to the default for anything unknown. */
    public static function resolve(?string $key): string
    {
        return $key !== null && isset(self::ALL[$key]) ? $key : self::DEFAULT;
    }
}
