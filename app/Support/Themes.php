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
        'studio' => ['name' => 'Studio', 'description' => 'Warm paper, sunny yellow and bold rounded headlines. Friendly for salons and shops.', 'font' => 'Bricolage Grotesque', 'swatches' => ['#fbfaf6', '#ffc83d', '#171612']],
        'coral' => ['name' => 'Coral', 'description' => 'Soft peach with coral buttons and elegant serif headlines. Clean and professional.', 'font' => 'Fraunces (serif)', 'swatches' => ['#fff8f2', '#ff7043', '#242b38']],
        'ocean' => ['name' => 'Ocean', 'description' => 'Cool blue and deep navy with crisp, modern type. Good for classes, tutors and services.', 'font' => 'Inter', 'swatches' => ['#f4f7ff', '#46c0ff', '#14205e']],
        'roastery' => ['name' => 'Roastery', 'description' => 'Cream, espresso brown and burnt orange with italic serif accents. Cozy for cafés and food.', 'font' => 'Fraunces (serif)', 'swatches' => ['#f7f0e7', '#e0703a', '#2d1d16']],
        'slate' => ['name' => 'Slate', 'description' => 'Quiet white and slate with a teal accent and tighter corners. Neat for repair and tech services.', 'font' => 'Inter', 'swatches' => ['#f8fafc', '#22b3cf', '#16202f']],
        'market' => ['name' => 'Market', 'description' => 'Fresh greens with a lime accent. Made for groceries, sari-sari stores and farm produce.', 'font' => 'Bricolage Grotesque', 'swatches' => ['#f7faf4', '#a9dc5e', '#133d30']],
    ];

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    /** A valid theme key, falling back to the default for anything unknown. */
    public static function resolve(?string $key): string
    {
        return $key !== null && isset(self::ALL[$key]) ? $key : self::DEFAULT;
    }
}
