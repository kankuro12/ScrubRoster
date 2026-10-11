<?php

namespace App\Services\Marketing;

use Illuminate\Support\Collection;

class FrontPageContent
{
    public function faqs(): array
    {
        return [
            [
                'question' => 'Who is ScrubRoster for?',
                'answer' => 'ScrubRoster is built for commercial cleaning companies and facilities teams. Managers can organize properties, schedule crews, verify attendance, and review completed work in a dedicated company workspace.',
            ],
            [
                'question' => 'How do cleaners sign in and record attendance?',
                'answer' => 'Cleaners can use their company portal or a manager-issued QR badge. QR badges support quick sign-in at a depot kiosk or on a mobile device, while location-aware attendance helps managers check arrivals.',
            ],
            [
                'question' => 'Can the same cleaner work for more than one company?',
                'answer' => 'Yes. The same email address can be used in separate company workspaces. Each company manages its own personnel, schedules, and records.',
            ],
            [
                'question' => 'How do Google Calendar and Google Sheets connect?',
                'answer' => 'Subscribe to your company calendar feed to view shifts and tasks in Google Calendar, Apple Calendar, or Outlook. Google Sheets can import attendance, tasks, and personnel from your workspace feeds. Refresh timing depends on the connected app.',
            ],
            [
                'question' => 'How long is the trial, and which plan should I choose?',
                'answer' => 'Create your company workspace, then review plans in the billing area. Each plan card lists its employee limit, included features, monthly and yearly prices, and configured trial duration. Choose a plan that covers your employee count; trial lengths vary by plan.',
            ],
            [
                'question' => 'How does subscription billing work?',
                'answer' => 'Monthly and yearly options are available in your workspace billing area, with card payments through Stripe and PayPal support. You can review plans and upgrade from there. Contact your workspace administrator for questions about billing or ending a subscription.',
            ],
        ];
    }

    public function schema(Collection $packages, array $faqs): array
    {
        $application = [
            '@type' => 'SoftwareApplication',
            '@id' => url('/').'#software',
            'name' => 'ScrubRoster',
            'operatingSystem' => 'Web',
            'applicationCategory' => 'BusinessApplication',
            'description' => 'Schedule cleaning teams, verify attendance, and review completed work in one company workspace.',
            'creator' => ['@id' => url('/').'#organization'],
        ];

        if ($packages->isNotEmpty()) {
            $application['offers'] = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'USD',
                'lowPrice' => number_format((float) $packages->min('price_monthly'), 2, '.', ''),
                'highPrice' => number_format((float) $packages->max('price_monthly'), 2, '.', ''),
                'offerCount' => $packages->count(),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'ScrubRoster',
                    'url' => url('/'),
                    'logo' => asset('logo.jpg'),
                ],
                $application,
                [
                    '@type' => 'FAQPage',
                    '@id' => url('/').'#faq',
                    'mainEntity' => array_map(fn (array $faq) => [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                    ], $faqs),
                ],
            ],
        ];
    }
}
