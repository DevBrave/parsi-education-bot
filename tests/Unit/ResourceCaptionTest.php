<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Resource;
use Tests\TestCase;

class ResourceCaptionTest extends TestCase
{
    public function test_build_telegram_caption_without_description(): void
    {
        $resource = new Resource([
            'title' => 'Sample Exam 2024',
        ]);
        $resource->setRelation('course', new Course(['name' => 'Algorithms']));

        $caption = $resource->buildTelegramCaption('en');

        $this->assertStringContainsString('Algorithms', $caption);
        $this->assertStringContainsString('Sample Exam 2024', $caption);
        $this->assertStringContainsString('Verified by PARSI', $caption);
        $this->assertStringNotContainsString('Description:', $caption);
    }

    public function test_build_telegram_caption_with_description(): void
    {
        $resource = new Resource([
            'title' => 'Midterm Review',
            'description' => 'Important formulas and cheat sheet for Chapters 1-5.',
        ]);
        $resource->setRelation('course', new Course(['name' => 'Physics I']));

        $caption = $resource->buildTelegramCaption('en');

        $this->assertStringContainsString('Physics I', $caption);
        $this->assertStringContainsString('Midterm Review', $caption);
        $this->assertStringContainsString('Important formulas and cheat sheet for Chapters 1-5.', $caption);
        $this->assertStringContainsString('📝 <i>', $caption);
    }

    public function test_build_telegram_caption_with_suffix_and_middle_section(): void
    {
        $resource = new Resource([
            'title' => 'Lecture Slides',
            'description' => 'Week 1 Introduction.',
        ]);
        $resource->setRelation('course', new Course(['name' => 'Calculus']));

        $caption = $resource->buildTelegramCaption('en', '(Part 1/3)', '📥 Custom Section');

        $this->assertStringContainsString('Lecture Slides (Part 1/3)', $caption);
        $this->assertStringContainsString('Week 1 Introduction.', $caption);
        $this->assertStringContainsString('📥 Custom Section', $caption);
    }
}

