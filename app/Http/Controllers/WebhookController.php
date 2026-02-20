<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebhookPawapayRequest;
use App\Jobs\ProcessPawapayWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __invoke(WebhookPawapayRequest $request): JsonResponse
    {
        $rawPayload = $request->getContent();

        $payload = json_decode($rawPayload, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Malformed payload'], Response::HTTP_BAD_REQUEST);
        }

        ProcessPawapayWebhookJob::dispatch($payload);

        Log::channel('payments')->info('Webhook accepted for async processing.', [
            'payload' => $payload,
        ]);

        return response()->json(['message' => 'Webhook accepted'], Response::HTTP_ACCEPTED);
    }
}
