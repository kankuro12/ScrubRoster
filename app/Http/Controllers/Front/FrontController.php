<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\Marketing\FrontPageContent;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FrontController extends Controller
{
    public function index(FrontPageContent $content): View
    {
        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get(['id', 'name', 'description', 'price_monthly', 'price_yearly',
                'max_employees', 'trial_days', 'features', 'is_popular']);

        $faqs = $content->faqs();

        return view('front.index', [
            'packages' => $packages,
            'currentUser' => auth()->user(),
            'faqs' => $faqs,
            'schemaJsonLd' => $content->schema($packages, $faqs),
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [
            [
                'loc' => url('/'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => url('/register'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.9',
            ],
            [
                'loc' => url('/login'),
                'lastmod' => now()->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
        ];

        $xml = view('front.sitemap', compact('urls'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
