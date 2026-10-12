<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Billing\StripeWebhookService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeWebhookService $service): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            return response()->json(['error' => 'Stripe webhook is not configured.'], 503);
        }
        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), $secret);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid Stripe signature or payload.'], 400);
        }
        if (! $event->id || ! $event->type || ! isset($event->data->object)) {
            return response()->json(['error' => 'Invalid Stripe event.'], 400);
        }
        try {
            $service->handle($event->toArray());
        } catch (DomainException $e) {
            report($e);
            return response()->json(['error' => 'Stripe event does not match local billing records.'], 422);
        }

        return response()->json(['received' => true]);
    }
}
