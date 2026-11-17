<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): Response
    {
        if (config('nova.stripe.fake')) {
            abort(404);
        }

        $payments->handleStripeWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature'),
        );

        return response()->noContent();
    }
}
