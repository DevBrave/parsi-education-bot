<?php

namespace App\Telegram\Handlers;

use App\Models\ResourceFile;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;

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
        $lang = $user->preferred_language ?? 'en';
        
        $normalizedQuery = mb_strtolower($query);
        $queryKey = $normalizedQuery === '' ? 'default_latest' : md5($normalizedQuery);
        $cacheKey = "search:inline:{$lang}:{$queryKey}";

        $results = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($query, $lang) {
            $resources = app(\App\Services\ResourceSearchService::class)
                ->query($query)->take(20)->get();
            $items = [];
            $channel = config('telegram.channel_username', 'PARSI_POLITO');
            $bot = config('telegram.bot_username', 'parsi_politobot');
            $botUsername = ltrim($bot, '@');

            foreach ($resources as $resource) {
                $courseName = $resource->course?->name ? e($resource->course->name) : 'Course Material';
                $allFiles = $resource->files;
                $fileCount = $allFiles->count();

                // Build official PARSI caption using the unified Resource method
                $caption = $resource->buildTelegramCaption($lang);

                $getAllUrl = "https://t.me/{$botUsername}?start=res_{$resource->id}";
                $courseUrl = $resource->course_id ? "https://t.me/{$botUsername}?start=course_{$resource->course_id}" : null;
                $primaryFile = $allFiles->firstWhere(fn($f) => filled($f->telegram_file_id)) ?? $allFiles->first();
                $primaryFileId = $primaryFile?->id ?? 0;
                $reportUrl = "https://t.me/{$botUsername}?start=report_{$resource->id}_{$primaryFileId}";

                $hasMissingDrive = $allFiles->contains(fn($f) => blank($f->drive_file_id));

                // 1) Single file with telegram_file_id: send directly as document or photo
                if ($fileCount === 1 && filled($allFiles->first()->telegram_file_id)) {
                    $file = $allFiles->first();
                    $keyboard = [];
                    if ($file->drive_file_id) {
                        $keyboard[] = [
                            ['text' => __('bot.resources.download_drive', [], $lang), 'url' => "https://drive.google.com/file/d/{$file->drive_file_id}/view"],
                        ];
                    }
                    if ($courseUrl) {
                        $keyboard[] = [
                            ['text' => __('bot.resources.open_course_in_bot', [], $lang), 'url' => $courseUrl],
                        ];
                    }
                    $keyboard[] = [
                        ['text' => __('bot.reports.button', [], $lang), 'url' => $reportUrl],
                    ];

                    if ($this->isPhotoFile($file)) {
                        $items[] = [
                            'type' => 'photo',
                            'id' => 'res_' . $resource->id,
                            'title' => "📄 ".($resource->title ?: $file->original_filename),
                            'photo_file_id' => $file->telegram_file_id,
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} (1 file)",
                            'reply_markup' => ['inline_keyboard' => $keyboard],
                        ];
                    } else {
                        $items[] = [
                            'type' => 'document',
                            'id' => 'res_' . $resource->id,
                            'title' => "📄 ".($resource->title ?: $file->original_filename),
                            'document_file_id' => $file->telegram_file_id,
                            'mime_type' => $file->mime_type ?: 'application/pdf',
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} (1 file)",
                            'reply_markup' => ['inline_keyboard' => $keyboard],
                        ];
                    }

                // 2) Multi-file resource, BUT any of them lacks a Google Drive link:
                // Send one of the files directly in Telegram and put buttons for all files, course page, and report!
                } elseif ($hasMissingDrive && $allFiles->contains(fn($f) => filled($f->telegram_file_id))) {
                    $primaryFile = $allFiles->firstWhere(fn($f) => filled($f->telegram_file_id));

                    $partCaption = $resource->buildTelegramCaption($lang, "(1/{$fileCount})");

                    $keyboard = [
                        [
                            ['text' => __('bot.resources.get_all_in_bot', [], $lang), 'url' => $getAllUrl],
                        ],
                    ];

                    if ($courseUrl) {
                        $keyboard[] = [
                            ['text' => __('bot.resources.open_course_in_bot', [], $lang), 'url' => $courseUrl],
                        ];
                    }

                    $keyboard[] = [
                        ['text' => __('bot.reports.button', [], $lang), 'url' => $reportUrl],
                    ];

                    if ($this->isPhotoFile($primaryFile)) {
                        $items[] = [
                            'type' => 'photo',
                            'id' => 'res_' . $resource->id,
                            'title' => "📁 {$resource->title} ",
                            'photo_file_id' => $primaryFile->telegram_file_id,
                            'caption' => $partCaption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} ({$fileCount} files)",
                            'reply_markup' => ['inline_keyboard' => $keyboard],
                        ];
                    } else {
                        $items[] = [
                            'type' => 'document',
                            'id' => 'res_' . $resource->id,
                            'title' => "📁 {$resource->title} ",
                            'document_file_id' => $primaryFile->telegram_file_id,
                            'mime_type' => $primaryFile->mime_type ?: 'application/pdf',
                            'caption' => $partCaption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} ({$fileCount} files)",
                            'reply_markup' => ['inline_keyboard' => $keyboard],
                        ];
                    }

                // 3) All files have Google Drive links:
                // Send clean article message with Drive links + button to get all in bot + report
                } else {
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

                    $downloadSection = $linksText !== ''
                        ? "📥 <b>Download Links:</b>\n\n" . $linksText
                        : null;

                    $messageText = $resource->buildTelegramCaption($lang, null, $downloadSection);

                    $keyboard = [
                        [
                            ['text' => __('bot.resources.get_all_in_bot', [], $lang), 'url' => $getAllUrl],
                        ],
                    ];

                    if ($courseUrl) {
                        $keyboard[] = [
                            ['text' => __('bot.resources.open_course_in_bot', [], $lang), 'url' => $courseUrl],
                        ];
                    }

                    $keyboard[] = [
                        ['text' => __('bot.reports.button', [], $lang), 'url' => $reportUrl],
                    ];

                    $items[] = [
                        'type' => 'article',
                        'id' => 'res_' . $resource->id,
                        'title' => "📁 {$resource->title}" ,
                        'description' => "{$courseName} ({$fileCount} ".($fileCount === 1 ? 'file' : 'files').')',
                        'input_message_content' => [
                            'message_text' => $messageText,
                            'parse_mode' => 'HTML',
                            'disable_web_page_preview' => true,
                        ],
                        'reply_markup' => ['inline_keyboard' => $keyboard],
                    ];
                }
            }

            return $items;
        });

        // Defensive check: ensure $results is a valid array
        if (! is_array($results)) {
            Cache::forget($cacheKey);
            $results = [];
        }

        $response = $this->telegram->answerInlineQuery($queryId, $results, cacheTime: 1, isPersonal: true);

        // Fallback: If Telegram API rejects document/photo file_ids, instantly retry with 100% reliable articles
        if ($response && ! $response->json('ok')) {
            bot_log('telegram')->warning("Telegram rejected inline query #{$queryId}, retrying with article fallback", [
                'error' => $response->json('description'),
            ]);
            Cache::forget($cacheKey);
            $fallbackResults = $this->convertToArticleFallback($results);
            $this->telegram->answerInlineQuery($queryId, $fallbackResults, cacheTime: 1, isPersonal: true);
        }
    }

    /**
     * Check if a resource file is a Telegram photo.
     */
    protected function isPhotoFile(?ResourceFile $file): bool
    {
        if (! $file) {
            return false;
        }

        if (str_starts_with($file->telegram_file_id ?? '', 'AgAC')) {
            return true;
        }

        if (str_starts_with($file->mime_type ?? '', 'image/')) {
            return true;
        }

        $ext = strtolower(pathinfo($file->original_filename ?? '', PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Convert any failing document/photo results to standard articles that Telegram always accepts.
     */
    protected function convertToArticleFallback(array $results): array
    {
        $articles = [];
        foreach ($results as $item) {
            if (($item['type'] ?? '') === 'article') {
                $articles[] = $item;
                continue;
            }

            $caption = $item['caption'] ?? ($item['title'] ?? 'Material');
            $articles[] = [
                'type' => 'article',
                'id' => $item['id'] ?? uniqid('res_'),
                'title' => $item['title'] ?? 'Material',
                'description' => $item['description'] ?? '',
                'input_message_content' => [
                    'message_text' => $caption,
                    'parse_mode' => 'HTML',
                ],
                'reply_markup' => $item['reply_markup'] ?? null,
            ];
        }

        return $articles;
    }
}
