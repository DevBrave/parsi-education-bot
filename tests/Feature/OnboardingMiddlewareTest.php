<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\Track;
use App\Models\User;
use App\Telegram\TelegramUpdateDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnboardingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['telegram.bot_token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => true])]);
    }

    #[DataProvider('languages')]
    public function test_language_selection_completes_onboarding_without_a_program(string $language): void
    {
        $sender = ['id' => 5001, 'first_name' => 'Alex'];
        $this->start($sender);

        $prompt = $this->lastTelegramRequest('sendMessage');
        $this->assertSame(
            ['onboard_lang_en', 'onboard_lang_it', 'onboard_lang_fa'],
            array_column($prompt['reply_markup']['inline_keyboard'][0], 'callback_data'),
        );

        $user = User::where('telegram_user_id', $sender['id'])->firstOrFail();
        $this->assertNull($user->preferred_language);
        $this->assertNull($user->program_id);

        $this->press($user, "onboard_lang_{$language}");

        $this->assertSame($language, $user->refresh()->preferred_language);
        $this->assertNull($user->program_id);
        $this->assertSame(
            __('bot.onboarding.completed', ['name' => 'Alex'], $language),
            $this->lastTelegramRequest('editMessageText')['text'],
        );
        $this->assertSame(__('bot.onboarding.ready', [], $language), $this->lastTelegramRequest('sendMessage')['text']);
        $this->assertArrayHasKey('keyboard', $this->lastTelegramRequest('sendMessage')['reply_markup']);

        $this->start($sender);
        $this->assertSame(
            __('bot.menu.welcome', ['name' => 'Alex'], $language),
            $this->lastTelegramRequest('sendMessage')['text'],
        );
    }

    public static function languages(): array
    {
        return [['en'], ['it'], ['fa']];
    }

    #[DataProvider('resourceActions')]
    public function test_resource_picker_filters_by_degree_and_reuses_the_saved_program(
        string $entry,
        string $degreePrefix,
        string $programPrefix,
        string $yearPrefix,
    ): void {
        $bachelor = $this->program('Computer Engineering', 'BSc');
        $master = $this->program('Data Science', 'MSc');
        $inactive = $this->program('Archived Computing', 'BSc', false);
        $user = User::factory()->create(['role' => User::ROLE_STUDENT, 'program_id' => $inactive->id]);

        $degrees = $this->press($user, $entry);
        $this->assertContains($degreePrefix.'BSc', $degrees);
        $this->assertContains($degreePrefix.'MSc', $degrees);
        $this->assertContains('back_edu_hub', $degrees);

        $programs = $this->press($user, $degreePrefix.'BSc');
        $this->assertContains($programPrefix.$bachelor->id, $programs);
        $this->assertNotContains($programPrefix.$master->id, $programs);
        $this->assertNotContains($programPrefix.$inactive->id, $programs);
        $this->assertContains($degreePrefix.'BSc', $this->press($user, $programPrefix.$inactive->id));

        $year = $yearPrefix.$bachelor->id.'_all_1';
        $this->assertContains($year, $this->press($user, $programPrefix.$bachelor->id));
        $this->assertSame($bachelor->id, $user->refresh()->program_id);
        $this->assertContains($year, $this->press($user, $entry));
    }

    public static function resourceActions(): array
    {
        return [
            'browse' => ['edu_browse', 'browse_degree_', 'prog_', 'course_year_'],
            'submit' => ['edu_submit_start', 'submit_degree_', 'submit_prog_', 'submit_year_'],
        ];
    }

    public function test_leaving_browse_track_selection_does_not_save_an_unfinished_choice(): void
    {
        $program = $this->program('Computer Engineering', 'MSc');
        $track = Track::create(['program_id' => $program->id, 'name' => 'Networks', 'is_active' => true]);
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->assertContains('browse_degree_MSc', $this->press($user, 'edu_browse'));
        $this->assertContains('prog_'.$program->id, $this->press($user, 'browse_degree_MSc'));
        $tracks = $this->press($user, 'prog_'.$program->id);
        $this->assertContains('track_'.$track->id, $tracks);
        $this->assertContains('browse_degree_MSc', $tracks);
        $this->assertNull($user->refresh()->program_id);

        $this->assertContains('edu_browse_program', $this->press($user, 'browse_degree_MSc'));
        $this->assertContains('back_edu_hub', $this->press($user, 'edu_browse_program'));
        $this->assertContains('edu_browse', $this->press($user, 'back_edu_hub'));
        $this->assertContains('browse_degree_MSc', $this->press($user, 'edu_browse'));

        $this->press($user, 'browse_degree_MSc');
        $this->press($user, 'prog_'.$program->id);
        $this->assertContains("course_year_{$program->id}_all_1", $this->press($user, 'track_'.$track->id));
        $this->assertSame($program->id, $user->refresh()->program_id);
        $this->assertSame($track->id, $user->track_id);
    }

    public function test_changing_program_from_submission_years_stays_in_submission(): void
    {
        $default = $this->program('Computer Engineering', 'MSc');
        $other = $this->program('Data Science', 'MSc');
        $defaultTrack = $default->tracks()->create(['name' => 'Networks', 'is_active' => true]);
        $selectedTrack = $other->tracks()->create(['name' => 'Machine Learning', 'is_active' => true]);
        $otherTrack = $other->tracks()->create(['name' => 'Data Engineering', 'is_active' => true]);
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'program_id' => $default->id,
            'track_id' => $defaultTrack->id,
        ]);
        $selectedCourse = $this->courseForTrack($other, $selectedTrack, 'ML100');
        $otherCourse = $this->courseForTrack($other, $otherTrack, 'DE100');

        $years = $this->press($user, 'edu_submit_start');
        $this->assertContains("submit_year_{$default->id}_all_1", $years);
        $this->assertContains('edu_submit_program', $years);
        $this->assertContains('submit_degree_MSc', $this->press($user, 'edu_submit_program'));
        $this->assertContains('submit_prog_'.$other->id, $this->press($user, 'submit_degree_MSc'));
        $tracks = $this->press($user, 'submit_prog_'.$other->id);
        $this->assertContains('submit_track_'.$selectedTrack->id, $tracks);
        $this->assertContains('submit_degree_MSc', $tracks);

        $year = "submit_year_{$other->id}_all_1";
        $this->assertContains($year, $this->press($user, 'submit_track_'.$selectedTrack->id));
        $this->assertSame($default->id, $user->refresh()->program_id);
        $this->assertSame($defaultTrack->id, $user->track_id);

        $selectedCourseButton = "submit_course_{$selectedCourse->id}_{$other->id}_all";
        $otherCourseButton = "submit_course_{$otherCourse->id}_{$other->id}_all";
        $courses = $this->press($user, $year);
        $this->assertContains($selectedCourseButton, $courses);
        $this->assertNotContains($otherCourseButton, $courses);

        $this->press($user, "submit_track_{$other->id}_all");
        $this->assertContains($otherCourseButton, $this->press($user, $year));
        $this->assertSame($defaultTrack->id, $user->refresh()->track_id);
    }

    public function test_keyword_search_opens_without_a_program(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->assertContains('search_prompt', $this->press($user, 'back_edu_hub'));
        $this->press($user, 'search_prompt');

        $this->assertSame(['step' => 'awaiting_search_query'], cache()->get("user_search_state:{$user->id}"));
        $this->assertNull($user->refresh()->program_id);
    }

    private function courseForTrack(Program $program, Track $track, string $code): Course
    {
        $course = Course::create(['name' => $track->name, 'course_code' => $code, 'is_active' => true]);
        $course->programs()->attach($program);
        $course->tracks()->attach($track);

        return $course;
    }

    private function program(string $name, string $degree, bool $active = true): Program
    {
        return Program::create([
            'name' => $name,
            'degree_level' => $degree,
            'university' => 'Politecnico di Torino',
            'is_active' => $active,
        ]);
    }

    private function start(array $sender): void
    {
        app(TelegramUpdateDispatcher::class)->dispatch([
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => $sender['id'], 'type' => 'private'],
                'from' => $sender,
                'text' => '/start',
            ],
        ]);
    }

    private function press(User $user, string $data): array
    {
        $requestCount = Http::recorded()->count();
        app(TelegramUpdateDispatcher::class)->dispatch([
            'callback_query' => [
                'id' => $data,
                'from' => ['id' => $user->telegram_user_id, 'first_name' => $user->first_name],
                'message' => ['chat' => ['id' => $user->telegram_user_id, 'type' => 'private'], 'message_id' => 10],
                'data' => $data,
            ],
        ]);

        return collect($this->lastTelegramRequest('editMessageText', $requestCount)['reply_markup']['inline_keyboard'] ?? [])
            ->collapse()
            ->pluck('callback_data')
            ->all();
    }

    private function lastTelegramRequest(string $method, int $skip = 0): Request
    {
        $pair = Http::recorded()->slice($skip)->last(fn (array $pair) => str_ends_with($pair[0]->url(), '/'.$method));
        $this->assertNotNull($pair, "Telegram did not receive {$method}");

        return $pair[0];
    }
}
