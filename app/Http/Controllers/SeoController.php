<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Response;

/** robots.txt and sitemap.xml, so live business pages can be found in search engines. */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Private or one-off pages: dashboards, sign-in flows, review/result steps and customer areas.
            'Disallow: /app',
            'Disallow: /admin',
            'Disallow: /b/*/my',
            'Disallow: /b/*/r/',
            'Disallow: /b/*/access/',
            'Disallow: /b/*/chat',
            'Disallow: /b/*/slots',
            'Disallow: /*?*layout=',
            'Disallow: /*?*palette=',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('landing'), 'lastmod' => null, 'alternates' => true],
            ['loc' => route('legal.privacy'), 'lastmod' => null, 'alternates' => false],
            ['loc' => route('legal.terms'), 'lastmod' => null, 'alternates' => false],
        ];
        $pages = ['appointments' => 'public.book', 'orders' => 'public.order', 'quotations' => 'public.quote', 'service_requests' => 'public.request'];
        Business::query()->whereNotNull('activated_at')->where('status', 'active')->orderBy('id')
            ->each(function (Business $b) use (&$urls, $pages) {
                $lastmod = $b->updated_at?->toAtomString();
                $urls[] = ['loc' => route('public.home', $b), 'lastmod' => $lastmod, 'alternates' => true];
                foreach ($pages as $module => $route) {
                    if ($b->moduleEnabled($module)) {
                        $urls[] = ['loc' => route($route, $b), 'lastmod' => $lastmod, 'alternates' => true];
                    }
                }
                $urls[] = ['loc' => route('public.privacy', $b), 'lastmod' => $lastmod, 'alternates' => true];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u['loc']).'</loc>'.($u['lastmod'] ? '<lastmod>'.e($u['lastmod']).'</lastmod>' : '');
            if ($u['alternates']) {
                foreach (['en', 'fil'] as $lang) {
                    $xml .= '<xhtml:link rel="alternate" hreflang="'.$lang.'" href="'.e($u['loc'].'?lang='.$lang).'"/>';
                }
            }
            $xml .= "</url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
