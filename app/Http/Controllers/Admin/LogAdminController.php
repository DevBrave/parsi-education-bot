<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogAdminController extends Controller
{
    /**
     * Map of supported log channels and human-readable metadata.
     */
    protected array $channels = [
        'master' => [
            'name' => 'Master Application',
            'icon' => '⚡',
            'filename' => 'laravel.log',
            'description' => 'Global application log (uncaught exceptions, framework, background jobs)',
        ],
        'telegram' => [
            'name' => 'Telegram Bot',
            'icon' => '📱',
            'filename' => 'telegram',
            'description' => 'Telegram webhook updates, bot commands, message delivery & callbacks',
        ],
        'drive' => [
            'name' => 'Google Drive',
            'icon' => '📁',
            'filename' => 'drive',
            'description' => 'Google Drive uploads, folder creation, backups, and file deletions',
        ],
        'rbac' => [
            'name' => 'RBAC & Audit',
            'icon' => '🛡️',
            'filename' => 'rbac',
            'description' => 'Role checks, permissions, resource approval & moderation actions',
        ],
        'auth' => [
            'name' => 'Authentication',
            'icon' => '🔑',
            'filename' => 'auth',
            'description' => 'Admin logins, password changes, token refreshes, session events',
        ],
        'api' => [
            'name' => 'External APIs',
            'icon' => '🌐',
            'filename' => 'api',
            'description' => 'HTTP clients, webhook outgoing calls, third-party integrations',
        ],
    ];

    /**
     * Display the Live Log Console.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user || ! $user->canViewLogs()) {
            abort(403, 'Unauthorized. Only Super Admins and Moderators can access system logs.');
        }

        $activeChannel = $request->input('channel', 'master');
        if (! array_key_exists($activeChannel, $this->channels)) {
            $activeChannel = 'master';
        }
        
        $logPath = $this->resolveLogPath($activeChannel);
        $initialLines = [];
        $fileSizeFormatted = '0 KB';
        $fileSizeBytes = 0;
        
        if (File::exists($logPath)) {
            $fileSizeBytes = File::size($logPath);
            $fileSizeFormatted = $this->formatBytes($fileSizeBytes);
            $initialLines = $this->readLastLines($logPath, 150);
        }

       
        
        // Compute size stats for all channels
        $channelStats = [];
        foreach ($this->channels as $key => $meta) {
            $path = $this->resolveLogPath($key);
            $size = File::exists($path) ? File::size($path) : 0;
            $channelStats[$key] = [
                'name' => $meta['name'],
                'icon' => $meta['icon'],
                'size' => $this->formatBytes($size),
                'bytes' => $size,
                'exists' => File::exists($path),
            ];
        }

        return view('admin.logs.index', [
            'channels' => $this->channels,
            'channelStats' => $channelStats,
            'activeChannel' => $activeChannel,
            'initialLines' => $initialLines,
            'fileSizeFormatted' => $fileSizeFormatted,
            'fileSizeBytes' => $fileSizeBytes,
            'logPath' => $logPath,
        ]);
    }

    /**
     * Poll new log entries since a given byte position (Smart Short-Polling).
     * Returns JSON immediately and exits in ~2-5ms, freeing PHP workers.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canViewLogs()) {
            abort(403);
        }

        $channel = $request->input('channel', 'master');
        if (! array_key_exists($channel, $this->channels)) {
            $channel = 'master';
        }

        $logPath = $this->resolveLogPath($channel);

        if (! File::exists($logPath)) {
            return response()->json([
                'lines' => [],
                'new_pos' => 0,
                'file_size' => 0,
                'file_size_formatted' => '0 KB',
                'truncated' => false,
            ]);
        }

        clearstatcache(false, $logPath);
        $fileSize = (int) filesize($logPath);
        $sincePos = (int) $request->input('since_pos', $fileSize);

        $truncated = false;
        if ($fileSize < $sincePos) {
            // Log file was truncated or rotated
            $sincePos = 0;
            $truncated = true;
        }

        $lines = [];
        $newPos = $sincePos;

        if ($fileSize > $sincePos) {
            $handle = fopen($logPath, 'r');
            if ($handle) {
                fseek($handle, $sincePos);
                while (($rawLine = fgets($handle)) !== false) {
                    $parsed = $this->parseLogLine($rawLine);
                    if ($parsed) {
                        $lines[] = $parsed;
                    }
                }
                $newPos = ftell($handle);
                fclose($handle);
            }
        }

        return response()->json([
            'lines' => $lines,
            'new_pos' => $newPos,
            'file_size' => $fileSize,
            'file_size_formatted' => $this->formatBytes($fileSize),
            'truncated' => $truncated,
        ]);
    }

    /**
     * Stream new log entries in real-time via Server-Sent Events (SSE).
     */
    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canViewLogs()) {
            abort(403);
        }

        $channel = $request->input('channel', 'master');
        if (! array_key_exists($channel, $this->channels)) {
            $channel = 'master';
        }

        $logPath = $this->resolveLogPath($channel);

        // Save and close session to prevent blocking other admin requests during SSE
        $request->session()->save();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return response()->stream(function () use ($logPath) {
            // Disable execution time limit for SSE streaming
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }
            @ini_set('max_execution_time', '0');
            ignore_user_abort(true);

            // Disable output buffering
            if (ob_get_level()) {
                ob_end_clean();
            }

            if (! File::exists($logPath)) {
                echo "data: " . json_encode(['empty' => true, 'message' => 'Log file does not exist yet.']) . "\n\n";
                flush();
                return;
            }

            $handle = fopen($logPath, 'r');
            if (! $handle) {
                echo "data: " . json_encode(['error' => 'Unable to read log file.']) . "\n\n";
                flush();
                return;
            }

            // Seek to end of file to only stream NEW lines
            fseek($handle, 0, SEEK_END);
            $lastPos = ftell($handle);

            // Send initial ping
            echo "event: connected\ndata: " . json_encode(['status' => 'connected', 'channel' => basename($logPath)]) . "\n\n";
            flush();

            $timeout = time() + 300; // Stream up to 5 minutes per connection

            while (time() < $timeout) {
                if (connection_aborted()) {
                    break;
                }

                clearstatcache(false, $logPath);
                $currentSize = filesize($logPath);

                if ($currentSize < $lastPos) {
                    // Log file was truncated / cleared
                    fseek($handle, 0, SEEK_SET);
                    $lastPos = 0;
                    echo "event: cleared\ndata: " . json_encode(['status' => 'log_cleared']) . "\n\n";
                    flush();
                } elseif ($currentSize > $lastPos) {
                    fseek($handle, $lastPos);
                    while (($rawLine = fgets($handle)) !== false) {
                        $parsed = $this->parseLogLine($rawLine);
                        if ($parsed) {
                            echo "data: " . json_encode($parsed) . "\n\n";
                            flush();
                        }
                    }
                    $lastPos = ftell($handle);
                }

                // Send heartbeat every 15 seconds to prevent proxy timeouts
                echo ": heartbeat\n\n";
                flush();

                usleep(500000); // 0.5 seconds polling interval
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Download the selected channel log file.
     */
    public function download(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canViewLogs()) {
            abort(403);
        }

        $channel = $request->input('channel', 'master');
        $logPath = $this->resolveLogPath($channel);

        if (! File::exists($logPath)) {
            abort(404, 'Log file not found.');
        }

        $downloadName = "parsi_polito_{$channel}_" . date('Y-m-d_His') . ".log";

        return response()->download($logPath, $downloadName, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Clear / empty the selected log file (Super Admin only).
     */
    public function clear(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Only Super Admins can clear system logs.');
        }

        $channel = $request->input('channel', 'master');
        $logPath = $this->resolveLogPath($channel);

        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        return back()->with('success', "Log file for channel '{$channel}' was cleared successfully.");
    }

    /**
     * Resolve the filesystem path for a specific log channel.
     */
    protected function resolveLogPath(string $channel): string
    {
        if ($channel === 'master') {
            return storage_path('logs/laravel.log');
        }

        // Daily rotating channel: check today's file first, e.g. telegram-2026-10-20.log
        $todayFile = storage_path("logs/{$channel}-" . date('Y-m-d') . ".log");
        if (File::exists($todayFile)) {
            return $todayFile;
        }

        // Fallback to static name if exists, e.g. telegram.log
        $staticFile = storage_path("logs/{$channel}.log");
        if (File::exists($staticFile)) {
            return $staticFile;
        }

        return $todayFile;
    }

    /**
     * Read the last N lines of a file without loading the entire file into memory.
     */
    protected function readLastLines(string $filepath, int $maxLines = 100): array
    {
        if (! File::exists($filepath) || File::size($filepath) === 0) {
            return [];
        }
        
        $handle = fopen($filepath, 'r');
        $lines = [];
        $buffer = '';
        $chunkSize = 4096;
        
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);
        $pos = $fileSize;
        
        while ($pos > 0 && count($lines) < $maxLines) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($handle, $pos);
            
            $chunk = fread($handle, $readSize);
            $buffer = $chunk . $buffer;
            
            $splitLines = explode("\n", $buffer);
            // Keep the first partial line in buffer for the next chunk
            $buffer = array_shift($splitLines);
            
            while (! empty($splitLines) && count($lines) < $maxLines) {
                $line = array_pop($splitLines);
                $parsed = $this->parseLogLine($line);
                if ($parsed) {
                    $lines[] = $parsed;
                }
            }
        }
        
        fclose($handle);


        // Reverse to preserve chronological order (oldest to newest)
        return array_reverse($lines);
    }

    /**
     * Parse a standard Monolog/Laravel log line into a structured record.
     */
    public function parseLogLine(string $rawLine): ?array
    {
        $line = trim($rawLine);
        if (empty($line)) {
            return null;
        }

        // Match Monolog pattern: [YYYY-MM-DD HH:MM:SS] environment.LEVEL: Message {context}
        $pattern = '/^\[(?P<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?P<env>[a-zA-Z0-9_\-]+)\.(?P<level>[A-Z]+): (?P<message>.*?)(?P<context>\{.*\}|\[.*\])?$/s';

        if (preg_match($pattern, $line, $matches)) {
            $level = strtoupper($matches['level']);
            $context = null;
            if (! empty($matches['context'])) {
                $decoded = json_decode($matches['context'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $context = $decoded;
                }
            }

            return [
                'date' => $matches['date'],
                'env' => $matches['env'],
                'level' => $level,
                'message' => trim($matches['message']),
                'context' => $context,
                'raw' => $line,
            ];
        }

        // Fallback for stack trace continuation lines
        return [
            'date' => '',
            'env' => '',
            'level' => 'TRACE',
            'message' => $line,
            'context' => null,
            'raw' => $line,
        ];
    }

    /**
     * Human-readable byte formatting.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
