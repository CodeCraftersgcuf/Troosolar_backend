<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MonoWebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonoWebhookController extends Controller
{
    public function __construct(
        private readonly MonoWebhookProcessor $processor
    ) {}

    /**
     * POST /api/webhooks/mono
     */
    public function handle(Request $request)
    {
        $secret = $request->header('mono-webhook-secret');
        if (! $this->processor->verifySecret($secret)) {
            Log::warning('Mono webhook rejected: invalid secret');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        if (empty($payload)) {
            return response()->json(['message' => 'Empty payload'], 400);
        }

        try {
            $this->processor->process($payload);
        } catch (\Throwable $e) {
            Log::error('Mono webhook handler error: ' . $e->getMessage());
        }

        return response()->json(['received' => true], 200);
    }
}
