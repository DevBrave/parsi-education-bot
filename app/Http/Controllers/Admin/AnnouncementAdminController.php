<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAnnouncementDelivery;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AnnouncementAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->admin($request);

        return view('admin.announcements.index', [
            'announcements' => Announcement::withCount('deliveries')->latest('id')->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->admin($request);

        return view('admin.announcements.form', [
            'announcement' => new Announcement(['languages' => [], 'include_menu_button' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $this->admin($request);
        $announcement = Announcement::create($this->fields($request) + ['created_by' => $admin->id]);

        return to_route('admin.announcements.show', $announcement)->with('success', 'Draft saved.');
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $admin = $this->admin($request);

        $counts = $announcement->deliveries()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return view('admin.announcements.show', [
            'announcement' => $announcement,
            'counts' => $counts,
            'deliveries' => $announcement->queued_at
                ? $announcement->deliveries()->with('user:id,first_name,username')->orderBy('id')->paginate(30)
                : null,
            'canTestSend' => $admin->telegram_user_id > 0,
        ]);
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        $this->admin($request);
        abort_if($announcement->queued_at !== null, 409, 'Queued announcements are kept for delivery history.');

        return view('admin.announcements.form', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->admin($request);
        $fields = $this->fields($request);

        DB::transaction(function () use ($announcement, $fields): void {
            $draft = Announcement::query()->lockForUpdate()->findOrFail($announcement->id);
            abort_if($draft->queued_at !== null, 409, 'Queued announcements are kept for delivery history.');
            $draft->update($fields);
        });

        return to_route('admin.announcements.show', $announcement)->with('success', 'Draft updated.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->admin($request);
        $deleted = Announcement::query()->whereKey($announcement->id)->whereNull('queued_at')->delete();
        abort_unless($deleted === 1, 409, 'Queued announcements are kept for delivery history.');

        return to_route('admin.announcements.index')->with('success', 'Draft deleted.');
    }

    public function testSend(Request $request, Announcement $announcement, TelegramService $telegram): RedirectResponse
    {
        $admin = $this->admin($request);
        abort_unless($admin->telegram_user_id > 0, 422, 'Link your Telegram account before sending a test.');

        try {
            $response = $telegram->sendAnnouncement($admin, $announcement);
            if ($response->successful() && $response->json('ok') === true) {
                return back()->with('success', 'Test sent to your Telegram account.');
            }
        } catch (ConnectionException) {
            return back()->with('error', 'Telegram did not confirm the test send. Check your chat before trying again.');
        }

        return back()->with('error', 'Telegram could not send the test. Make sure your account has started the bot.');
    }

    public function queue(Request $request, Announcement $announcement): RedirectResponse
    {
        $admin = $this->admin($request);
        $driver = config('queue.connections.'.config('queue.default').'.driver');
        if (! $driver || in_array($driver, ['sync', 'null', 'deferred', 'background'], true)) {
            return back()->with('error', 'Announcements require a background queue worker.');
        }

        DB::transaction(function () use ($announcement): void {
            $record = Announcement::query()->lockForUpdate()->findOrFail($announcement->id);
            if ($record->queued_at !== null) {
                return;
            }

            $students = $record->matchingStudents()->orderBy('id')->lockForUpdate()->pluck('id');

            if ($students->isEmpty()) {
                return;
            }

            $now = now();
            foreach ($students->chunk(200) as $chunk) {
                AnnouncementDelivery::insert($chunk->map(fn (int $studentId) => [
                    'announcement_id' => $record->id,
                    'user_id' => $studentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }
            $record->update(['queued_at' => $now]);
        }, 3);

        if ($announcement->refresh()->queued_at === null) {
            return back()->with('info', 'No students match these filters. Your draft is still editable.');
        }

        $pending = $announcement->deliveries()
            ->where('status', AnnouncementDelivery::PENDING)
            ->where(fn ($query) => $query->whereNull('retry_at')->orWhere('retry_at', '<=', now()))
            ->orderBy('id')
            ->pluck('id');
        if ($pending->isEmpty()) {
            return back()->with('info', 'No deliveries are ready to queue.');
        }

        try {
            foreach ($pending as $index => $id) {
                SendAnnouncementDelivery::dispatch($id)->delay(1 + intdiv($index, 8));
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The queue is unavailable. Use Resume pending deliveries when it is ready.');
        }

        log_rbac('Announcement deliveries queued', $admin, [
            'announcement_id' => $announcement->id,
            'count' => $pending->count(),
        ]);

        return back()->with('success', 'Announcement queued for '.$pending->count().' deliveries.');
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canManageUsers(), 403);

        return $user;
    }

    /** @return array<string, mixed> */
    private function fields(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:4096'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['distinct', Rule::in(array_keys(Announcement::LANGUAGES))],
            'without_program' => ['sometimes', 'boolean'],
            'minimum_submissions' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'joined_before' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'include_menu_button' => ['sometimes', 'boolean'],
        ]);

        return [
            'title' => $data['title'],
            'message' => $data['message'],
            'languages' => array_values($data['languages'] ?? []),
            'without_program' => $request->boolean('without_program'),
            'minimum_submissions' => (int) ($data['minimum_submissions'] ?? 0),
            'joined_before' => $data['joined_before'] ?? null,
            'include_menu_button' => $request->boolean('include_menu_button'),
        ];
    }
}
