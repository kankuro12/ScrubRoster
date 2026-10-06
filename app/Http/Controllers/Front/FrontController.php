<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontController extends Controller
{
    public function index(Request $request): View
    {
        $packages = Package::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();

        return view('front.index', [
            'packages' => $packages,
            'currentUser' => auth()->user(),
            'schemaJsonLd' => $this->buildSchemaJsonLd(),
        ]);
    }

    public function sitemap(): \Illuminate\Http\Response
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

    protected function buildSchemaJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/') . '/#organization',
                    'name' => 'ScrubRoster',
                    'url' => url('/'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        '@id' => url('/') . '/#logo',
                        'url' => asset('logo.jpg'),
                        'caption' => 'ScrubRoster Logo',
                    ],
                    'sameAs' => [
                        'https://twitter.com/ScrubRoster',
                    ],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => url('/') . '/#software',
                    'name' => 'ScrubRoster',
                    'operatingSystem' => 'Web, iOS, Android',
                    'applicationCategory' => 'BusinessApplication',
                    'applicationSubCategory' => 'Workforce & Field Operations Management',
                    'offers' => [
                        '@type' => 'AggregateOffer',
                        'priceCurrency' => 'USD',
                        'lowPrice' => '49',
                        'highPrice' => '249',
                        'offerCount' => '3',
                    ],
                    'description' => 'Workforce scheduling, attendance dispatch, and operations platform designed specifically for commercial cleaning contractors and janitorial fleets.',
                    'featureList' => [
                        'Custom Dedicated Company Portals',
                        'Instant QR Badge Depot Check-In',
                        'Live Google Calendar Schedule Sync',
                        'Automated Google Sheets Attendance & Payroll Reports',
                        'GPS Geofenced Clock-In & Photo Proof Auditing',
                        'Flexible Subscription Plans with Card & PayPal Checkout',
                    ],
                    'creator' => [
                        '@id' => url('/') . '/#organization',
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => url('/') . '/#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'How does ScrubRoster help commercial cleaning companies?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'ScrubRoster provides an all-in-one workforce operations platform built specifically for commercial cleaning contractors, facilities managers, and turnover fleets. Each company gets a private, branded workspace to schedule shifts, dispatch crews, verify GPS attendance, and manage payroll.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'How does QR badge check-in work for cleaners?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Managers can generate digital or printable QR badges for each crew member. Cleaners simply scan their badge at a depot tablet kiosk or on a mobile device to clock in instantly for their shift—no passwords or typing required.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Can cleaners work for multiple cleaning contractors using the same email?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Yes. Cleaners can take shifts with multiple cleaning contractors using a single email address while keeping each contractor’s client data, rates, and shifts strictly private and secure.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'How does calendar and spreadsheet synchronization work?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Cleaning shifts automatically sync to worker and client calendars (Google Calendar, Apple, Outlook). Attendance hours and completed task records also sync live into Google Sheets for effortless payroll without manual data entry.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'What payment options are available for subscription plans?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'We accept all major credit and debit cards via Stripe as well as PayPal, with instant workspace activation and flexible monthly or yearly billing.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
