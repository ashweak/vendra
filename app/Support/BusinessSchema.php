<?php

namespace App\Support;

use App\Models\Business;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/** schema.org data for a business page, so search engines can show its hours, contact and prices. */
class BusinessSchema
{
    private const TYPES = [
        'salon' => 'BeautySalon',
        'retail' => 'Store',
        'cleaning' => 'HomeAndConstructionBusiness',
        'tutorial' => 'LocalBusiness',
        'general' => 'LocalBusiness',
    ];

    private const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public static function for(Business $b, Collection $hours, Collection $items): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => self::TYPES[$b->category] ?? 'LocalBusiness',
            '@id' => route('public.home', $b).'#business',
            'name' => $b->name,
            'url' => route('public.home', $b),
            'description' => $b->description ?: null,
            'telephone' => $b->phone ?: null,
            'image' => array_values(array_filter([$b->coverUrl(), $b->logoUrl()])) ?: null,
            'logo' => $b->logoUrl(),
            'address' => $b->address ? ['@type' => 'PostalAddress', 'streetAddress' => $b->address, 'addressCountry' => 'PH'] : null,
            'currenciesAccepted' => 'PHP',
            'openingHoursSpecification' => $hours->filter(fn ($h) => ! $h->is_closed && $h->opens_at && $h->closes_at)
                ->sortBy('weekday')->values()
                ->map(fn ($h) => [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => self::DAYS[$h->weekday] ?? null,
                    'opens' => substr((string) $h->opens_at, 0, 5),
                    'closes' => substr((string) $h->closes_at, 0, 5),
                ])->all() ?: null,
        ];
        $offers = $items->take(20)->map(fn ($i) => [
            '@type' => 'Offer',
            'price' => number_format($i->price_cents / 100, 2, '.', ''),
            'priceCurrency' => 'PHP',
            'itemOffered' => array_filter([
                '@type' => $i->type === 'service' ? 'Service' : 'Product',
                'name' => $i->name,
                'image' => $i->imageUrl(),
            ]),
        ])->values()->all();
        if ($offers) {
            $data['hasOfferCatalog'] = ['@type' => 'OfferCatalog', 'name' => $b->name, 'itemListElement' => $offers];
        }

        return array_filter($data, fn ($v) => $v !== null);
    }

    /** The <script type="application/ld+json"> block. JSON_HEX_TAG keeps "</script>" in names from ending it early. */
    public static function tag(Business $b, Collection $hours, Collection $items): HtmlString
    {
        $json = json_encode(self::for($b, $hours, $items), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

        return new HtmlString('<script type="application/ld+json">'.$json.'</script>');
    }
}
