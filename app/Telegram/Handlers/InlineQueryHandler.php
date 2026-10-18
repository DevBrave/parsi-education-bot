<?php

namespace App\Telegram\Handlers;

use App\Models\Resource;
use App\Models\User;
use App\Services\TelegramService;

class InlineQueryHandler
{
    public function __construct(
        protected TelegramService $telegram
    ) {}

    /**
     * Handle incoming inline query when a user types @bot_username <query> anywhere.
     * Only returns resources (no courses):
     * - 1 file: sends the real file document directly to the chat.
     * - Multiple files: sends a clean message with direct Google Drive links for each file.
     */
    public function handle(array $inlineQuery, User $user): void
    {
        $queryId = (string) ($inlineQuery['id'] ?? '');
        $query = trim($inlineQuery['query'] ?? '');

        // Search only in approved resources and their files / course
        if (mb_strlen($query) < 2) {
            $resources = Resource::with(['course', 'resourceType', 'files'])
                ->where('status', 'approved')
                ->latest()
                ->take(15)
                ->get();
        } else {
            $resources = Resource::with(['course', 'resourceType', 'files'])
                ->where('status', 'approved')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhereHas('course', function ($cq) use ($query) {
                            $cq->where('name', 'like', "%{$query}%")
                                ->orWhere('course_code', 'like', "%{$query}%");
                        })
                        ->orWhereHas('files', function ($fq) use ($query) {
                            $fq->where('original_filename', 'like', "%{$query}%");
                        });
                })
                ->take(20)
                ->get();
        }

        $results = [];
        $channel = config('telegram.channel_username', 'PARSI_POLITO');
        $bot = config('telegram.bot_username', 'parsi_politobot');
        $lang = $user->preferred_language ?? 'en';

        foreach ($resources as $resource) {
            $courseName = $resource->course?->name ? e($resource->course->name) : 'Course Material';
            $typeName = $resource->resourceType?->display_name ?? 'Resource';
            $allFiles = $resource->files;
            $fileCount = $allFiles->count();

            // Build official PARSI caption
            $caption = __('bot.resources.caption_header', [
                'course' => $courseName,
                'title' => e($resource->title),
            ], $lang);

            $caption .= __('bot.resources.caption_social_media', [
                'channel' => ltrim($channel, '@'),
                'bot' => ltrim($bot, '@'),
            ], $lang);

            // If the resource has only 1 file and has telegram_file_id -> send the file directly with verified caption
            if ($fileCount === 1 && filled($allFiles->first()->telegram_file_id)) {
                $file = $allFiles->first();

                $results[] = [
                    'type' => 'document',
                    'id' => 'res_' . $resource->id,
                    'title' => "📄 {$resource->title}",
                    'document_file_id' => $file->telegram_file_id,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'description' => "{$courseName} • {$typeName} (1 file)",
                ];
            } else {
                // If the resource has multiple parts/files -> provide Google Drive link for each file in that resource
                $linksText = '';
                foreach ($allFiles as $index => $file) {
                    $filename = $file->original_filename ?: ("File " . ($index + 1));
                    $url = null;
                    if ($file->drive_file_id) {
                        $url = "https://drive.google.com/file/d/{$file->drive_file_id}/view";
                    } elseif ($file->file_path) {
                        $url = url('/storage/' . $file->file_path);
                    }

                    if ($url) {
                        $linksText .= "• <a href=\"{$url}\">📄 " . e($filename) . "</a>\n";
                    } else {
                        $linksText .= "• 📄 " . e($filename) . "\n";
                    }
                }

                $messageText = __('bot.resources.caption_header', [
                    'course' => $courseName,
                    'title' => e($resource->title),
                ], $lang) . "\n\n" .
                "📥 <b>Download Links:</b>\n" . $linksText .
                __('bot.resources.caption_social_media', [
                    'channel' => ltrim($channel, '@'),
                    'bot' => ltrim($bot, '@'),
                ], $lang);

                $results[] = [
                    'type' => 'article',
                    'id' => 'res_' . $resource->id,
                    'title' => "📁 {$resource->title}" . ($fileCount > 1 ? " ({$fileCount} files)" : ''),
                    'description' => "{$courseName} • {$typeName}" . ($fileCount > 1 ? " ({$fileCount} files)" : ''),
                    'input_message_content' => [
                        'message_text' => $messageText,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true,
                    ],
                ];
            }
        }

        $this->telegram->answerInlineQuery($queryId, $results, cacheTime: 30);
    }
}
