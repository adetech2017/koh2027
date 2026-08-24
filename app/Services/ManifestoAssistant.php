<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Material;
use App\Models\PlatformPillar;

class ManifestoAssistant
{
    private const MAX_HISTORY_MESSAGES = 16;

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(apiKey: config('services.anthropic.key'));
    }

    /**
     * Ask a question grounded in the seeded manifesto content, given the prior
     * turns of the conversation (oldest first, each ['role' => ..., 'content' => ...]).
     */
    public function ask(array $history, string $question): string
    {
        $messages = [
            ...$this->sanitizeHistory($history),
            ['role' => 'user', 'content' => $question],
        ];

        $message = $this->client->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 1024,
            system: [
                ['type' => 'text', 'text' => $this->instructions()],
                [
                    'type' => 'text',
                    'text' => $this->knowledgeBase(),
                    'cacheControl' => ['type' => 'ephemeral', 'ttl' => '1h'],
                ],
            ],
            messages: $messages,
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return $block->text;
            }
        }

        return "Sorry, I wasn't able to put together an answer just now. Please try again.";
    }

    private function instructions(): string
    {
        return <<<TEXT
        You are the manifesto assistant for "The Lagos Promise" campaign platform.
        Answer questions from members of the public about the campaign's policies
        using ONLY the manifesto content provided below in the following knowledge-base
        message. Do not use outside knowledge about Lagos politics or invent policies
        that are not stated in the material.

        Rules:
        - If the manifesto material does not cover what's being asked, say so plainly
          and suggest the visitor check the Materials/Manifesto page for the full
          documents or use the Contact page — do not guess.
        - Keep answers conversational and concise (a few short paragraphs at most).
        - When relevant, mention which pillar (e.g. "People First", "Renewal") the
          answer comes from.
        - Do not discuss other candidates, parties, or make political attacks.
        TEXT;
    }

    private function knowledgeBase(): string
    {
        $sections = ["# The Lagos Promise — Manifesto Knowledge Base\n"];

        foreach (PlatformPillar::active()->get() as $pillar) {
            $sections[] = "## Pillar: {$pillar->title}\n{$pillar->summary}\n\n{$pillar->body}";
        }

        $documents = Material::query()
            ->where('category', 'manifesto')
            ->whereNotNull('extracted_text')
            ->orderBy('title')
            ->get(['title', 'extracted_text']);

        foreach ($documents as $document) {
            $sections[] = "## Full text: {$document->title}\n{$document->extracted_text}";
        }

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * Keep only well-formed, recent turns so a client can't inflate token
     * usage by sending an unbounded conversation history.
     */
    private function sanitizeHistory(array $history): array
    {
        $turns = [];

        foreach ($history as $turn) {
            if (!is_array($turn)) {
                continue;
            }

            $role = $turn['role'] ?? null;
            $content = $turn['content'] ?? null;

            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || $content === '') {
                continue;
            }

            $turns[] = ['role' => $role, 'content' => $content];
        }

        return array_slice($turns, -self::MAX_HISTORY_MESSAGES);
    }
}
