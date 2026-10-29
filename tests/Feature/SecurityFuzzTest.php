<?php

namespace Tests\Feature;

use App\Http\Controllers\TelegramWebhookController;
use App\Models\Course;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class SecurityFuzzTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['ok' => true])]);
    }

    public function test_webhook_rejects_missing_secret_token_when_configured(): void
    {
        Config::set('telegram.webhook_secret', 'super_secret_token_123');

        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => ['text' => '/start'],
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_rejects_spoofed_secret_token(): void
    {
        Config::set('telegram.webhook_secret', 'super_secret_token_123');

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'attacker_fake_token',
        ])->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => ['text' => '/start'],
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_accepts_valid_secret_token(): void
    {
        Config::set('telegram.webhook_secret', 'super_secret_token_123');

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'super_secret_token_123',
        ])->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'chat' => ['id' => 99999],
                'from' => ['id' => 99999, 'first_name' => 'ValidUser'],
                'text' => '/start',
            ],
        ]);

        $response->assertNoContent();
    }

    public function test_webhook_payload_fuzzing_resilience(): void
    {
        Config::set('telegram.webhook_secret', 'test_secret');
        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret'];

        $fuzzPayloads = [
            [], // Empty JSON
            ['update_id' => 'not_an_int'], // Wrong type
            ['message' => null], // Null message
            ['message' => []], // Empty message
            ['message' => ['chat' => []]], // Missing chat id
            ['inline_query' => ['id' => 'abc']], // Incomplete inline query
            ['callback_query' => ['id' => 'cb1', 'data' => str_repeat('A', 5000)]], // Overflow data
            [
                'update_id' => 9999,
                'message' => [
                    'chat' => ['id' => 123],
                    'from' => ['id' => 123, 'first_name' => "<script>alert('xss')</script>"],
                    'text' => str_repeat("'\"; DROP TABLE users; -- \x00", 200),
                ],
            ],
        ];

        foreach ($fuzzPayloads as $payload) {
            $response = $this->withHeaders($headers)->postJson('/api/telegram/webhook', $payload);
            // Must return 204 No Content without crashing with 500 error
            $this->assertContains($response->status(), [204, 200], 'Webhook crashed on fuzz payload: ' . json_encode($payload));
        }
    }

    public function test_search_handles_sqli_and_xss_injection_attempts(): void
    {
        $user = new User(['id' => 1, 'first_name' => 'Tester', 'preferred_language' => 'en']);
        $controller = app(TelegramWebhookController::class);

        $maliciousQueries = [
            "' OR '1'='1",
            "1; DROP TABLE courses; --",
            "<script>alert('xss')</script>",
            "UNION SELECT null, username, password FROM users --",
            str_repeat("A", 1000),
            "\x00\x01\x02\x03",
        ];

        foreach ($maliciousQueries as $query) {
            $this->invokeHandler($controller, 'handleMessage', [
                ['chat' => ['id' => 123], 'text' => "/search {$query}"],
                $user,
            ]);
        }

        // Search executed without throwing database exceptions
        $this->assertTrue(true);
    }

    public function test_admin_login_is_rate_limited_against_brute_force(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('admin.login.submit'), [
                'email' => 'wrong_admin@example.com',
                'password' => 'wrong_password_' . $i,
            ]);
        }

        // 11th request must be throttled with 429 Too Many Requests
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'wrong_admin@example.com',
            'password' => 'wrong_password_11',
        ]);

        $response->assertStatus(429);
    }

    public function test_rate_limiter_class_fuzzing_and_thresholds(): void
    {
        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        // 1. Threshold verification: 100 requests limit
        $standardKey = 'user_rate_test_key_100';
        $limiter->clear($standardKey);

        for ($i = 1; $i <= 100; $i++) {
            $this->assertFalse($limiter->tooManyAttempts($standardKey, 100));
            $limiter->hit($standardKey, 60);
        }

        // 101st request must trigger rate limit
        $this->assertTrue($limiter->tooManyAttempts($standardKey, 100));
        $this->assertSame(0, $limiter->remaining($standardKey, 100));
        $this->assertGreaterThan(0, $limiter->availableIn($standardKey));

        // 2. Fuzz testing RateLimiter keys with mutated hostile inputs (seeds + dictionaries)
        $seeds = ['user_123', 'ip_192.168.1.1', 'tg_999888', 'session_abc'];
        $dictionary = ['/', '\\', "\x00", "'", '"', '%', '🔥', 'سلام', str_repeat('A', 500)];

        for ($run = 1; $run <= 200; $run++) {
            $seed = $seeds[$run % count($seeds)];
            $dictToken = $dictionary[$run % count($dictionary)];
            $fuzzedKey = "fuzz_{$seed}_{$dictToken}_{$run}";

            // The rate limiter must never throw a fatal error on hostile key formats
            $this->assertFalse($limiter->tooManyAttempts($fuzzedKey, 3));

            // Hit 3 times to exhaust limit
            $limiter->hit($fuzzedKey, 30);
            $limiter->hit($fuzzedKey, 30);
            $limiter->hit($fuzzedKey, 30);

            // 4th hit must be throttled
            $this->assertTrue($limiter->tooManyAttempts($fuzzedKey, 3));

            // Resetting must unthrottle immediately
            $limiter->clear($fuzzedKey);
            $this->assertFalse($limiter->tooManyAttempts($fuzzedKey, 3));
        }

        $this->assertTrue(true);
    }

    public function test_burst_fuzz_production_queued_200_requests_within_30_seconds(): void
    {
        \Illuminate\Support\Facades\Queue::fake([
            \App\Jobs\ProcessTelegramUpdate::class,
        ]);

        Config::set('telegram.webhook_secret', 'burst_secret_key');
        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'burst_secret_key'];

        $startTime = microtime(true);
        $totalRequests = 200;
        $statuses = [];

        for ($i = 1; $i <= $totalRequests; $i++) {
            $type = $i % 4;
            $payload = match ($type) {
                0 => [
                    'update_id' => 100000 + $i,
                    'message' => [
                        'message_id' => $i,
                        'chat' => ['id' => 1000 + ($i % 20)],
                        'from' => ['id' => 1000 + ($i % 20), 'first_name' => "Student_{$i}"],
                        'text' => ($i % 2 === 0) ? '/start' : "search query {$i}",
                    ],
                ],
                1 => [
                    'update_id' => 200000 + $i,
                    'callback_query' => [
                        'id' => "cb_{$i}",
                        'from' => ['id' => 1000 + ($i % 20)],
                        'message' => ['chat' => ['id' => 1000 + ($i % 20)], 'message_id' => 100],
                        'data' => ($i % 2 === 0) ? 'back_main' : "course_{$i}",
                    ],
                ],
                2 => [
                    'update_id' => 300000 + $i,
                    'inline_query' => [
                        'id' => "inline_{$i}",
                        'from' => ['id' => 1000 + ($i % 20)],
                        'query' => "math {$i}",
                        'offset' => '',
                    ],
                ],
                default => [
                    'update_id' => 400000 + $i,
                    'message' => [
                        'chat' => ['id' => 1000 + ($i % 20)],
                        'text' => str_repeat("fuzz_boundary_", 20) . "\x00<test>",
                    ],
                ],
            };

            $response = $this->withHeaders($headers)->postJson('/api/telegram/webhook', $payload);
            $code = $response->status();
            $statuses[$code] = ($statuses[$code] ?? 0) + 1;
        }

        $duration = microtime(true) - $startTime;

        // Assert 0 server 500 errors
        $this->assertArrayNotHasKey(500, $statuses, 'Server 500 error encountered during fuzz burst!');
        // In queued mode, 200 requests should easily complete in under 5 seconds (well below 30s)
        $this->assertLessThan(30.0, $duration, "200 queued requests took {$duration}s, exceeding 30s.");
        $successful = ($statuses[204] ?? 0) + ($statuses[200] ?? 0);
        $this->assertSame($totalRequests, $successful, "Expected {$totalRequests} successful responses, got {$successful}");
    }

    private function invokeHandler(object $instance, string $method, array $arguments): void
    {
        (new ReflectionMethod($instance, $method))->invokeArgs($instance, $arguments);
    }
}
