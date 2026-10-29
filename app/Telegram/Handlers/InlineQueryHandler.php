<?php

namespace App\Telegram\Handlers;

use App\Models\Resource;
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
        $isShortQuery = (mb_strlen($normalizedQuery) < 3);
        $queryKey = $isShortQuery ? 'default_latest' : md5($normalizedQuery);
        $cacheKey = "search:inline:{$lang}:{$queryKey}";

        $results = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($query, $isShortQuery, $normalizedQuery, $lang) {
            $baseQuery = Resource::with(['course', 'resourceType', 'files'])
                ->where('status', 'approved')
                ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM resource_files WHERE resource_files.resource_id = resources.id) THEN 0 ELSE 1 END ASC')
                ->latest();

            if ($isShortQuery) {
                $resources = $baseQuery->take(20)->get();
            } else {
                $tokens = array_filter(explode(' ', $normalizedQuery), fn($t) => mb_strlen(trim($t)) >= 1);
                if (empty($tokens)) {
                    $tokens = [$normalizedQuery];
                }

                // Course acronym mapping (e.g., 'os' -> Operating Systems, 'ca' -> Computer Architecture)
                $courseAcronymMap = Cache::remember('courses:acronym_map', now()->addHours(12), function () {
                    $map = [];
                    foreach (\App\Models\Course::all(['id', 'name']) as $c) {
                        $words = preg_split('/[\s\-_]+/', $c->name);
                        $acronym = '';
                        foreach ($words as $w) {
                            $w = trim($w);
                            if ($w !== '' && !in_array(strtolower($w), ['and', 'for', 'the', 'of', 'in', 'to', 'a', 'an', '&'])) {
                                $acronym .= mb_substr($w, 0, 1);
                            }
                        }
                        if (mb_strlen($acronym) >= 2) {
                            $map[mb_strtolower($acronym)][] = $c->id;
                        }
                    }
                    return $map;
                });

                foreach ($tokens as $token) {
                    $variants = [$token];
                    if (str_ends_with($token, 's') && mb_strlen($token) > 3) {
                        $variants[] = rtrim($token, 's');
                    }

                    $matchingCourseIds = $courseAcronymMap[$token] ?? [];

                    $baseQuery->where(function ($sub) use ($variants, $token, $matchingCourseIds) {
                        if (!empty($matchingCourseIds)) {
                            $sub->orWhereIn('course_id', $matchingCourseIds);
                        }

                        foreach ($variants as $v) {
                            $sub->orWhere('title', 'like', "%{$v}%")
                                ->orWhere('description', 'like', "%{$v}%")
                                ->orWhereHas('course', function ($cq) use ($v, $token) {
                                    $cq->where('name', 'like', "%{$v}%")
                                        ->orWhere('course_code', 'like', "%{$v}%");
                                    if (is_numeric($token)) {
                                        $cq->orWhere('year', (int) $token);
                                    }
                                })
                                ->orWhereHas('resourceType', function ($rq) use ($v) {
                                    $rq->where('code', 'like', "%{$v}%")
                                        ->orWhere('display_name', 'like', "%{$v}%");
                                })
                                ->orWhereHas('files', function ($fq) use ($v) {
                                    $fq->where('original_filename', 'like', "%{$v}%");
                                });
                        }
                    });
                }

                $resources = $baseQuery->take(20)->get();
            }

            $items = [];
            $channel = config('telegram.channel_username', 'PARSI_POLITO');
            $bot = config('telegram.bot_username', 'parsi_politobot');
            $botUsername = ltrim($bot, '@');

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
                    'bot' => $botUsername,
                ], $lang);

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
                            'title' => "📄 {$resource->title}",
                            'photo_file_id' => $file->telegram_file_id,
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} • {$typeName} (1 photo)",
                            'reply_markup' => ['inline_keyboard' => $keyboard],
                        ];
                    } else {
                        $items[] = [
                            'type' => 'document',
                            'id' => 'res_' . $resource->id,
                            'title' => "📄 {$resource->title}",
                            'document_file_id' => $file->telegram_file_id,
                            'mime_type' => $file->mime_type ?: 'application/pdf',
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} • {$typeName} (1 file)",
                            'reply_markup' => ['inline_keyboard' => $keyboard],
                        ];
                    }

                // 2) Multi-file resource, BUT any of them lacks a Google Drive link:
                // Send one of the files directly in Telegram and put buttons for all files, course page, and report!
                } elseif ($hasMissingDrive && $allFiles->contains(fn($f) => filled($f->telegram_file_id))) {
                    $primaryFile = $allFiles->firstWhere(fn($f) => filled($f->telegram_file_id));

                    $partCaption = __('bot.resources.caption_header', [
                        'course' => $courseName,
                        'title' => e($resource->title) . " (1/{$fileCount})",
                    ], $lang);

                    $partCaption .= __('bot.resources.caption_social_media', [
                        'channel' => ltrim($channel, '@'),
                        'bot' => $botUsername,
                    ], $lang);

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
                            'title' => "📁 {$resource->title} ({$fileCount} files)",
                            'photo_file_id' => $primaryFile->telegram_file_id,
                            'caption' => $partCaption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} • {$typeName} ({$fileCount} files)",
                            'reply_markup' => ['inline_keyboard' => $keyboard],
                        ];
                    } else {
                        $items[] = [
                            'type' => 'document',
                            'id' => 'res_' . $resource->id,
                            'title' => "📁 {$resource->title} ({$fileCount} files)",
                            'document_file_id' => $primaryFile->telegram_file_id,
                            'mime_type' => $primaryFile->mime_type ?: 'application/pdf',
                            'caption' => $partCaption,
                            'parse_mode' => 'HTML',
                            'description' => "{$courseName} • {$typeName} ({$fileCount} files)",
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
                        ? "📥 <b>Download Links:</b>\n\n" . $linksText . "\n"
                        : '';

                    $messageText = __('bot.resources.caption_header', [
                        'course' => $courseName,
                        'title' => e($resource->title),
                    ], $lang) . "\n\n" .
                    $downloadSection .
                    __('bot.resources.caption_social_media', [
                        'channel' => ltrim($channel, '@'),
                        'bot' => $botUsername,
                    ], $lang);

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
                        'title' => "📁 {$resource->title}" . ($fileCount > 1 ? " ({$fileCount} files)" : ''),
                        'description' => "{$courseName} • {$typeName}" . ($fileCount > 1 ? " ({$fileCount} files)" : ''),
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
