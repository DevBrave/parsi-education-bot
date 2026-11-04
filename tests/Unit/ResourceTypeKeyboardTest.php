<?php

namespace Tests\Unit;

use App\Models\ResourceType;
use App\Models\Resource;
use App\Telegram\Keyboards\ProgramMenuKeyboard;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ResourceTypeKeyboardTest extends TestCase
{
    public function test_moving_a_resource_invalidates_counts_for_both_courses(): void
    {
        $resource = new Resource;
        $resource->setRawAttributes(['course_id' => 42, 'resource_type_id' => 1, 'status' => 'approved'], true);
        $resource->course_id = 43;
        $resource->syncChanges();
        Cache::put('catalog:resource_type_counts:course_42', [1 => 2], 600);
        Cache::put('catalog:resource_type_counts:course_43', [1 => 5], 600);
        Cache::put('catalog:resource_type_counts:course_44', [1 => 8], 600);

        Resource::getEventDispatcher()->dispatch('eloquent.saved: '.Resource::class, $resource);

        $this->assertFalse(Cache::has('catalog:resource_type_counts:course_42'));
        $this->assertFalse(Cache::has('catalog:resource_type_counts:course_43'));
        $this->assertSame([1 => 8], Cache::get('catalog:resource_type_counts:course_44'));
    }

    public function test_approval_and_deletion_invalidate_the_course_counts(): void
    {
        $resource = new Resource;
        $resource->setRawAttributes(['course_id' => 42, 'resource_type_id' => 1, 'status' => 'pending'], true);
        $resource->status = 'approved';
        $resource->syncChanges();
        Cache::put('catalog:resource_type_counts:course_42', [], 600);

        Resource::getEventDispatcher()->dispatch('eloquent.saved: '.Resource::class, $resource);
        $this->assertFalse(Cache::has('catalog:resource_type_counts:course_42'));

        Cache::put('catalog:resource_type_counts:course_42', [1 => 1], 600);
        Resource::getEventDispatcher()->dispatch('eloquent.deleted: '.Resource::class, $resource);
        $this->assertFalse(Cache::has('catalog:resource_type_counts:course_42'));
    }

    public function test_cached_counts_are_shown_and_empty_types_are_hidden(): void
    {
        Cache::put('catalog:resource_type_counts:course_42', [1 => 3, 2 => 0], now()->addMinutes(10));
        $types = [
            ['id' => 1, 'display_name' => 'Notes'],
            ['id' => 2, 'display_name' => 'Exams'],
            ['id' => 3, 'display_name' => 'Books'],
        ];

        $rows = ProgramMenuKeyboard::resourceTypes($types, 42, 7, 'en', 2)['inline_keyboard'];

        $this->assertCount(2, $rows);
        $this->assertSame('📖 Notes (3)', $rows[0][0]['text']);
        $this->assertSame('resource_type_1_42_7_2_1', $rows[0][0]['callback_data']);
        $this->assertSame('course_year_7_2_1', $rows[1][0]['callback_data']);
    }

    public function test_empty_course_keeps_the_submission_and_back_buttons(): void
    {
        Cache::put('catalog:resource_type_counts:course_42', [], now()->addMinutes(10));

        $rows = ProgramMenuKeyboard::resourceTypes([
            ['id' => 1, 'display_name' => 'Notes'],
        ], 42, 7)['inline_keyboard'];

        $this->assertSame('noop', $rows[0][0]['callback_data']);
        $this->assertSame('submit_res_42', $rows[1][0]['callback_data']);
        $this->assertSame('course_year_7_all_1', $rows[2][0]['callback_data']);
    }

    public function test_model_types_and_other_languages_reuse_the_same_course_counts(): void
    {
        Cache::put('catalog:resource_type_counts:course_42', [1 => 5], now()->addMinutes(10));
        $type = new ResourceType(['display_name' => 'Notes']);
        $type->id = 1;

        $rows = ProgramMenuKeyboard::resourceTypes([$type], 42, 7, 'fa')['inline_keyboard'];

        $this->assertSame('📖 Notes (5)', $rows[0][0]['text']);
        $this->assertSame('resource_type_1_42_7_all_1', $rows[0][0]['callback_data']);
    }
}
