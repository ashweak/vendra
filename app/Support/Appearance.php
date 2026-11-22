<?php

namespace App\Support;

use App\Models\Business;

/**
 * How a business's customer pages look, chosen in two independent parts:
 *  - a layout: page structure, header and footer style, headline font (resources/views/public/home/{layout}.blade.php)
 *  - a palette: colors only (CSS tokens under [data-palette="…"] in resources/css/app.css)
 * Any layout works with any palette. Every palette was checked for WCAG AA text contrast.
 */
class Appearance
{
    public const DEFAULT_LAYOUT = 'classic';

    public const DEFAULT_PALETTE = 'sunny';

    public const LAYOUTS = [
        'classic' => ['name' => 'Classic', 'description' => 'Split welcome with a chat preview, a two-column price list and an info block.', 'font' => 'Bricolage Grotesque'],
        'brochure' => ['name' => 'Brochure', 'description' => 'Centered welcome, quick-action cards, service cards and an assistant band.', 'font' => 'Fraunces (serif)'],
        'spotlight' => ['name' => 'Spotlight', 'description' => 'Bold headline with stats, a swipeable item row and a ring-style how-it-works.', 'font' => 'Inter'],
        'cafe' => ['name' => 'Café', 'description' => 'Bento welcome with a featured item, scrolling categories and menu cards.', 'font' => 'Fraunces (serif)'],
        'panel' => ['name' => 'Panel', 'description' => 'App-style page: business card on the side, stat cards and a clean price table.', 'font' => 'Inter'],
        'shop' => ['name' => 'Shop', 'description' => 'Store banner with search, a category shelf and a product grid.', 'font' => 'Bricolage Grotesque'],
    ];

    public const PALETTES = [
        'sunny' => ['name' => 'Sunny', 'description' => 'Warm paper with sunny yellow. Bright and friendly.', 'swatches' => ['#fbfaf6', '#ffc83d', '#171612']],
        'coral' => ['name' => 'Coral', 'description' => 'Soft peach with coral buttons. Warm and professional.', 'swatches' => ['#fff8f2', '#ff7043', '#242b38']],
        'ocean' => ['name' => 'Ocean', 'description' => 'Cool blue with deep navy. Calm and trustworthy.', 'swatches' => ['#f4f7ff', '#46c0ff', '#14205e']],
        'espresso' => ['name' => 'Espresso', 'description' => 'Cream, espresso brown and burnt orange. Cozy.', 'swatches' => ['#f7f0e7', '#e0703a', '#2d1d16']],
        'slate' => ['name' => 'Slate', 'description' => 'Clean white and slate with a teal accent. Neat and modern.', 'swatches' => ['#f8fafc', '#22b3cf', '#16202f']],
        'garden' => ['name' => 'Garden', 'description' => 'Fresh greens with a lime accent. Natural and fresh.', 'swatches' => ['#f7faf4', '#a9dc5e', '#133d30']],
        'berry' => ['name' => 'Berry', 'description' => 'Blush pink with a berry accent. Playful and bold.', 'swatches' => ['#fff7fa', '#ff5c93', '#331828']],
        'lavender' => ['name' => 'Lavender', 'description' => 'Soft lilac with deep violet. Calm and elegant.', 'swatches' => ['#f8f6ff', '#a78bfa', '#251d4a']],
    ];

    /** Header and footer partials each layout uses (resources/views/public/partials). */
    public const CHROME = [
        'classic' => ['pill', 'simple'], 'brochure' => ['bar', 'dark'], 'spotlight' => ['split', 'dark'],
        'cafe' => ['stacked', 'dark'], 'panel' => ['bar', 'simple'], 'shop' => ['stacked', 'dark'],
    ];

    public static function resolveLayout(?string $key): string
    {
        return $key !== null && isset(self::LAYOUTS[$key]) ? $key : self::DEFAULT_LAYOUT;
    }

    public static function resolvePalette(?string $key): string
    {
        return $key !== null && isset(self::PALETTES[$key]) ? $key : self::DEFAULT_PALETTE;
    }

    /**
     * The visitor's preview choice for this business, kept in their session so it follows them to every page.
     * ?layout= / ?palette= set it, ?preview=off clears it. Only that visitor sees it; nothing is saved.
     */
    public static function preview(Business $business): array
    {
        $request = request();
        if (! $request->hasSession()) {
            return [];
        }
        $key = 'appearance_preview.'.$business->id;
        if ($request->query('preview') === 'off') {
            $request->session()->forget($key);

            return [];
        }
        $current = $request->session()->get($key, []);
        $next = $current;
        foreach (['layout' => self::LAYOUTS, 'palette' => self::PALETTES] as $part => $options) {
            $value = $request->query($part);
            if (is_string($value) && isset($options[$value])) {
                $next[$part] = $value;
            }
        }
        if ($next !== $current) {
            $request->session()->put($key, $next);
        }

        return $next;
    }

    /** Layout to render: the visitor's preview if any, else the saved one. */
    public static function layout(Business $business): string
    {
        return self::preview($business)['layout'] ?? self::resolveLayout($business->layout);
    }

    /** Palette to render: the visitor's preview if any, else the saved one. */
    public static function palette(Business $business): string
    {
        return self::preview($business)['palette'] ?? self::resolvePalette($business->palette);
    }
}
