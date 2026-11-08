<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\ResourceSearchService;
use App\Telegram\Handlers\MessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResourceSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_search_matches_acronyms_and_words_across_course_and_resource_type(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $user = User::factory()->create(['preferred_language' => 'en']);
        $course = Course::create(['name' => 'Operating Systems', 'course_code' => '01OS', 'is_active' => true]);
        $type = ResourceType::create(['code' => 'exam', 'display_name' => 'Exam', 'is_active' => true]);
        $resource = Resource::create([
            'title' => 'Winter assessment',
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'submitted_by' => $user->id,
            'status' => 'approved',
        ]);
        $pending = Resource::create([
            'title' => 'Winter assessment',
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'submitted_by' => $user->id,
            'status' => 'pending',
        ]);

        foreach (['OS', 'systems exams', "  OS   exams  "] as $query) {
            $ids = app(ResourceSearchService::class)->query($query)->pluck('id');
            $this->assertTrue($ids->contains($resource->id));
            $this->assertFalse($ids->contains($pending->id));

            app(MessageHandler::class)->executeSearch(12345, $user, $query);
        }

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && collect($request['reply_markup']['inline_keyboard'] ?? [])
                ->flatten(1)->contains('callback_data', "send_resource_{$resource->id}"));
    }
}
