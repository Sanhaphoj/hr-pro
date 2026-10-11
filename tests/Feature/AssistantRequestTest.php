<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\AssistantService;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the assistant actually sends to the Claude API, captured from the
 * SDK through a mocked HTTP transport — no network, nothing billed.
 */
class AssistantRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<int, Response>  $responses */
    private function assistant(array $responses, array &$sent): AssistantService
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($sent));

        return new AssistantService(new Guzzle(['handler' => $stack]));
    }

    private function message(array $content, string $stop = 'end_turn'): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
            'content' => $content, 'stop_reason' => $stop, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ]));
    }

    public function test_the_request_uses_the_model_effort_fallbacks_and_a_cached_rules_block(): void
    {
        config(['hrpro.ai.api_key' => 'sk-test', 'hrpro.ai.model' => 'claude-opus-5-5']);
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);
        $sent = [];

        $result = $this->assistant([$this->message([['type' => 'text', 'text' => 'เหลือ 6 วันครับ']])], $sent)
            ->reply($user, 'เหลือวันลากี่วัน', [
                ['role' => 'user', 'content' => 'สวัสดี'],
                ['role' => 'assistant', 'content' => 'สวัสดีครับ'],
            ]);

        $this->assertSame(['reply' => 'เหลือ 6 วันครับ', 'ok' => true], $result);
        $this->assertCount(1, $sent);

        $request = $sent[0]['request'];
        $body = json_decode((string) $request->getBody(), true);

        $this->assertStringEndsWith('/v1/messages', $request->getUri()->getPath());
        $this->assertSame('sk-test', $request->getHeaderLine('x-api-key'));
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame('low', $body['output_config']['effort']);
        $this->assertArrayNotHasKey('thinking', $body);
        $this->assertSame(['type' => 'ephemeral'], $body['system'][0]['cache_control']);
        $this->assertArrayNotHasKey('cache_control', $body['system'][1]);
        $this->assertSame(['user', 'assistant', 'user'], array_column($body['messages'], 'role'));
        $this->assertSame('เหลือวันลากี่วัน', $body['messages'][2]['content']);
    }

    public function test_thinking_blocks_are_skipped_and_text_joined(): void
    {
        config(['hrpro.ai.api_key' => 'sk-test']);
        $user = User::factory()->create();
        $sent = [];

        $result = $this->assistant([$this->message([
            ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig'],
            ['type' => 'text', 'text' => 'ตอบ'],
        ])], $sent)->reply($user, 'ถาม');

        $this->assertSame('ตอบ', $result['reply']);
    }

    public function test_a_refusal_gets_a_polite_reply(): void
    {
        config(['hrpro.ai.api_key' => 'sk-test']);
        $user = User::factory()->create();
        $sent = [];

        $result = $this->assistant([$this->message([], 'refusal')], $sent)->reply($user, 'ถาม');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('ตอบคำถามนี้ไม่ได้', $result['reply']);
    }

    public function test_an_api_error_becomes_a_friendly_message(): void
    {
        config(['hrpro.ai.api_key' => 'sk-test']);
        $user = User::factory()->create();
        $sent = [];
        $error = fn () => new Response(500, ['Content-Type' => 'application/json'],
            json_encode(['type' => 'error', 'error' => ['type' => 'api_error', 'message' => 'boom']]));

        // The SDK retries a 500 twice before giving up.
        $result = $this->assistant([$error(), $error(), $error()], $sent)->reply($user, 'ถาม');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('ผู้ช่วยตอบไม่ได้ในขณะนี้', $result['reply']);
    }
}
