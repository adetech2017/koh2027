<?php

namespace App\Http\Controllers;

use Anthropic\Core\Exceptions\APIStatusException;
use App\Services\ManifestoAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ManifestoChatController extends Controller
{
    public function respond(Request $request, ManifestoAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:16'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
        ]);

        try {
            $reply = $assistant->ask($validated['history'] ?? [], $validated['message']);
        } catch (APIStatusException $e) {
            if ($e->type?->value === 'rate_limit_error') {
                return response()->json([
                    'message' => "I'm getting a lot of questions right now — please try again in a moment.",
                ], 429);
            }

            Log::error('Manifesto chat failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => "Sorry, I couldn't reach the manifesto assistant just now. Please try again shortly.",
            ], 502);
        }

        return response()->json(['message' => $reply]);
    }
}
