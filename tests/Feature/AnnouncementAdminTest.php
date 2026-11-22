<?php

namespace Tests\Feature;

use App\Jobs\SendAnnouncementDelivery;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\Course;
use App\Models\Program;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\TelegramUpdateDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnnouncementAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'database', 'telegram.bot_token' => 'test-token']);
        Http::preventStrayRequests();
    }

    public function test_admins_can_manage_drafts_without_querying_the_audience(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        Queue::fake();
        DB::enableQueryLog();

        try {
            $this->get(route('admin.announcements.create'))->assertOk()->assertSee('Audience filters');
            $this->post(route('admin.announcements.store'), [
                'title' => 'Onboarding update',
                'message' => 'The menu is available.',
                'languages' => ['fa'],
                'without_program' => 1,
                'minimum_submissions' => 2,
            ])->assertRedirect();
            $draft = Announcement::firstOrFail();

            $this->get(route('admin.announcements.index'))->assertOk()->assertSee('Onboarding update');
            $this->get(route('admin.announcements.edit', $draft))->assertOk()->assertSee('Edit announcement');
            $this->get(route('admin.announcements.show', $draft))->assertOk()->assertSee('Queue announcement');
            $this->put(route('admin.announcements.update', $draft), [
                'title' => 'Updated message',
                'message' => 'View the updated menu.',
                'languages' => ['en', 'it'],
            ])->assertRedirect(route('admin.announcements.show', $draft));
            $this->assertSame(['en', 'it'], $draft->refresh()->languages);
            $this->delete(route('admin.announcements.destroy', $draft))->assertRedirect(route('admin.announcements.index'));

            $queries = array_column(DB::getQueryLog(), 'query');
            $this->assertFalse(collect($queries)->contains(fn (string $query) => str_contains($query, 'submitted_by') || str_contains($query, 'preferred_language')));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        Queue::assertNothingPushed();
        Http::assertNothingSent();
        $this->assertModelMissing($draft);
    }

    public function test_draft_validation_rejects_invalid_content_and_filters(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->post(route('admin.announcements.store'), [
            'title' => '',
            'message' => str_repeat('x', 4097),
            'languages' => ['de', 'en', 'en'],
            'minimum_submissions' => -1,
            'joined_before' => 'tomorrow',
        ])->assertSessionHasErrors(['title', 'message', 'languages.0', 'languages.1', 'minimum_submissions', 'joined_before']);

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_queue_resolves_the_combined_filters_at_submission_time(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        Queue::fake();
        $announcement = $this->announcement([
            'languages' => ['fa', 'it'],
            'without_program' => true,
            'minimum_submissions' => 2,
            'joined_before' => '2026-11-10T00:00',
        ]);

        $persian = $this->student();
        $italian = $this->student(['preferred_language' => 'it']);
        $program = Program::create(['name' => 'Computer Engineering', 'degree_level' => 'BSc']);
        $excluded = [
            $this->student(['preferred_language' => 'en']),
            $this->student(['program_id' => $program->id]),
            $this->student(['created_at' => '2026-11-10 00:00:00']),
            $this->student(['telegram_user_id' => null]),
            $this->student(['is_blocked' => true]),
            $this->student(['role' => User::ROLE_MODERATOR]),
            $this->student(['role' => User::ROLE_VIP]),
        ];
        foreach ([$persian, $italian, ...$excluded] as $user) {
            $this->addSubmission($user, 1);
            $this->addSubmission($user, 1);
        }
        $oneSubmission = $this->student();
        $this->addSubmission($oneSubmission, 2);
        $this->addSubmission($oneSubmission, 0);

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('success');

        $this->assertSame([$persian->id, $italian->id], $announcement->deliveries()->orderBy('user_id')->pluck('user_id')->all());
        Queue::assertPushed(SendAnnouncementDelivery::class, 2);
        Http::assertNothingSent();
        $this->get(route('admin.announcements.show', $announcement))->assertOk()->assertSee('2 recipients');
        $this->put(route('admin.announcements.update', $announcement), ['title' => 'Changed', 'message' => 'Changed'])->assertConflict();
        $this->delete(route('admin.announcements.destroy', $announcement))->assertConflict();

        $this->post(route('admin.announcements.queue', $announcement));
        $this->assertSame(2, $announcement->deliveries()->count());
    }

    public function test_queue_keeps_an_announcement_editable_when_nobody_matches(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        Queue::fake();
        $announcement = $this->announcement(['minimum_submissions' => 1]);
        $this->student();

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('info');

        $this->assertNull($announcement->refresh()->queued_at);
        Queue::assertNothingPushed();
    }

    #[DataProvider('languageAudiences')]
    public function test_no_major_filter_includes_unset_languages_when_no_language_filter_is_selected(array $languages, array $expected): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        Queue::fake();
        $announcement = $this->announcement(['languages' => $languages, 'without_program' => true]);
        $students = [
            'en' => $this->student(['preferred_language' => 'en']),
            'it' => $this->student(['preferred_language' => 'it']),
            'fa' => $this->student(['preferred_language' => 'fa']),
            'unset' => $this->student(['preferred_language' => null]),
        ];

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('success');

        $expectedIds = array_map(fn (string $language) => $students[$language]->id, $expected);
        $this->assertSame($expectedIds, $announcement->deliveries()->orderBy('user_id')->pluck('user_id')->all());
        Queue::assertPushed(SendAnnouncementDelivery::class, count($expectedIds));
        Http::assertNothingSent();
    }

    public static function languageAudiences(): array
    {
        return [
            'any language' => [[], ['en', 'it', 'fa', 'unset']],
            'English' => [['en'], ['en']],
            'Italian' => [['it'], ['it']],
            'Persian' => [['fa'], ['fa']],
        ];
    }

    public function test_queue_stages_all_recipients_across_insert_chunks(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        Queue::fake();
        $announcement = $this->announcement();
        User::factory()->count(201)->create(['role' => User::ROLE_STUDENT]);

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('success');

        $this->assertSame(201, $announcement->deliveries()->count());
        Queue::assertPushed(SendAnnouncementDelivery::class, 201);
        $jobs = Queue::pushed(SendAnnouncementDelivery::class);
        $this->assertSame(1, $jobs->first()->delay);
        $this->assertGreaterThan(1, $jobs->last()->delay);
        Http::assertNothingSent();
    }

    public function test_queue_keeps_the_draft_when_only_a_synchronous_driver_is_available(): void
    {
        config(['queue.default' => 'sync']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $announcement = $this->announcement();
        $this->student();

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('error');

        $this->assertNull($announcement->refresh()->queued_at);
        $this->assertSame(0, $announcement->deliveries()->count());
        Http::assertNothingSent();
    }

    public function test_worker_delivers_later_and_resume_skips_sent_or_newly_ineligible_students(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        Http::fake(['*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);
        $announcement = $this->announcement(['without_program' => true, 'message' => 'Hello <student> & welcome.']);
        $recipient = $this->student();
        $changed = $this->student();
        $pending = $this->student();

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('success');
        Http::assertNothingSent();
        $this->assertDatabaseCount('jobs', 3);

        $program = Program::create(['name' => 'Computer Engineering', 'degree_level' => 'BSc']);
        $changed->update(['program_id' => $program->id]);
        $this->travel(2)->seconds();
        $queue = Queue::connection('database');
        for ($index = 0; $index < 2; $index++) {
            $job = $queue->pop();
            $this->assertNotNull($job);
            $job->fire();
        }

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['chat_id'] === $recipient->telegram_user_id
            && $request['text'] === 'Hello &lt;student&gt; &amp; welcome.'
            && $request['reply_markup']['inline_keyboard'][0][0]['text'] === __('bot.menu.open_menu', [], 'fa')
            && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === 'open_main_menu');
        $this->assertDatabaseHas('announcement_deliveries', [
            'user_id' => $recipient->id,
            'status' => AnnouncementDelivery::SENT,
            'telegram_message_id' => 42,
        ]);
        $this->assertDatabaseHas('announcement_deliveries', ['user_id' => $changed->id, 'status' => AnnouncementDelivery::SKIPPED]);

        $lostJob = $queue->pop();
        $this->assertNotNull($lostJob);
        $lostJob->delete();
        $newStudent = $this->student();

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('success');
        $this->assertDatabaseCount('jobs', 1);
        $this->travel(2)->seconds();
        $resumedJob = $queue->pop();
        $this->assertNotNull($resumedJob);
        $resumedJob->fire();
        $this->assertDatabaseHas('announcement_deliveries', ['user_id' => $pending->id, 'status' => AnnouncementDelivery::SENT]);
        $this->assertDatabaseMissing('announcement_deliveries', ['user_id' => $newStudent->id]);

        $this->post(route('admin.announcements.queue', $announcement))->assertSessionHas('info');
        $sent = $announcement->deliveries()->where('user_id', $recipient->id)->firstOrFail();
        (new SendAnnouncementDelivery($sent->id))->handle(app(TelegramService::class));
        Http::assertSentCount(2);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_telegram_rate_limits_wait_and_ambiguous_requests_are_held(): void
    {
        Http::fake(['*/sendMessage' => Http::sequence()
            ->push(['ok' => false, 'error_code' => 429, 'parameters' => ['retry_after' => 15]], 429)
            ->push(['ok' => false], 503)]);
        $announcement = $this->announcement();
        $recipient = $this->student();
        $delivery = $announcement->deliveries()->make();
        $delivery->user()->associate($recipient);
        $delivery->save();

        $job = (new SendAnnouncementDelivery($delivery->id))->withFakeQueueInteractions();
        $telegram = app(TelegramService::class);
        $job->handle($telegram);
        $job->assertReleased(15);
        $this->assertSame(AnnouncementDelivery::PENDING, $delivery->refresh()->status);

        (new SendAnnouncementDelivery($delivery->id))->handle($telegram);
        Http::assertSentCount(1);
        $this->travel(15)->seconds();
        $job->handle($telegram);
        $this->assertSame(AnnouncementDelivery::UNCONFIRMED, $delivery->refresh()->status);
        $job->handle($telegram);
        Http::assertSentCount(2);
    }

    public function test_definitive_telegram_rejection_is_recorded_without_resending(): void
    {
        Http::fake(['*/sendMessage' => Http::response(['ok' => false, 'error_code' => 403], 403)]);
        $announcement = $this->announcement(['include_menu_button' => false]);
        $delivery = $announcement->deliveries()->make();
        $delivery->user()->associate($this->student());
        $delivery->save();

        $telegram = app(TelegramService::class);
        (new SendAnnouncementDelivery($delivery->id))->handle($telegram);
        (new SendAnnouncementDelivery($delivery->id))->handle($telegram);

        $this->assertDatabaseHas('announcement_deliveries', [
            'id' => $delivery->id,
            'status' => AnnouncementDelivery::FAILED,
            'telegram_error_code' => 403,
        ]);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => ! isset($request['reply_markup']));
    }

    public function test_test_send_reaches_only_the_admin_and_students_can_open_the_menu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $student = $this->student();
        $draft = $this->announcement();
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->actingAs($admin)->post(route('admin.announcements.test', $draft))->assertSessionHas('success');
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['chat_id'] === $admin->telegram_user_id);
        $this->assertNull($draft->refresh()->queued_at);

        app(TelegramUpdateDispatcher::class)->dispatch([
            'callback_query' => [
                'id' => 'open-menu',
                'from' => ['id' => $student->telegram_user_id, 'first_name' => $student->first_name],
                'message' => ['chat' => ['id' => $student->telegram_user_id, 'type' => 'private'], 'message_id' => 10],
                'data' => 'open_main_menu',
            ],
        ]);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === $student->telegram_user_id
            && isset($request['reply_markup']['keyboard']));
    }

    public function test_other_staff_cannot_manage_or_send_announcements(): void
    {
        Queue::fake();
        $draft = $this->announcement();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_MODERATOR]));

        $this->get(route('admin.announcements.index'))->assertForbidden();
        $this->post(route('admin.announcements.store'), ['title' => 'Unauthorized', 'message' => 'No'])->assertForbidden();
        $this->put(route('admin.announcements.update', $draft), ['title' => 'Unauthorized', 'message' => 'No'])->assertForbidden();
        $this->delete(route('admin.announcements.destroy', $draft))->assertForbidden();
        $this->post(route('admin.announcements.queue', $draft))->assertForbidden();
        $this->post(route('admin.announcements.test', $draft))->assertForbidden();
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_admin_without_a_telegram_account_cannot_test_send(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'telegram_user_id' => null]));
        $draft = $this->announcement();

        $this->get(route('admin.announcements.show', $draft))->assertOk()->assertDontSee('Send test to my Telegram');
        $this->post(route('admin.announcements.test', $draft))->assertUnprocessable();

        Http::assertNothingSent();
    }

    private function announcement(array $changes = []): Announcement
    {
        return Announcement::create($changes + [
            'title' => 'Test message',
            'message' => 'The bot is ready.',
            'languages' => [],
        ])->refresh();
    }

    private function student(array $changes = []): User
    {
        return User::factory()->create($changes + [
            'role' => User::ROLE_STUDENT,
            'preferred_language' => 'fa',
            'created_at' => '2026-11-09 15:00:00',
        ])->refresh();
    }

    private function addSubmission(User $student, int $fileCount): void
    {
        $course = Course::firstOrCreate(['course_code' => 'CS101'], ['name' => 'Computer Science']);
        $type = ResourceType::firstOrCreate(['code' => 'notes'], ['display_name' => 'Notes']);
        $submission = $student->submittedResources()->create([
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'title' => 'Student notes',
        ]);

        for ($index = 0; $index < $fileCount; $index++) {
            $submission->files()->create([
                'original_filename' => "notes-{$index}.pdf",
                'mime_type' => 'application/pdf',
                'size_bytes' => 1024,
                'sha256' => hash('sha256', (string) $index),
            ]);
        }
    }
}
