@extends('admin.layout')

@section('title', $announcement->title)

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h1>{{ $announcement->title }}</h1>
                <p>{{ $announcement->queued_at ? 'Queued '.$announcement->queued_at->format('d M Y, H:i') : 'Draft' }}</p>
            </div>
            <a class="btn btn-secondary" href="{{ route('admin.announcements.index') }}">All announcements</a>
        </div>
        <div style="padding: 1.5rem; display: grid; gap: 1.25rem;">
            <div>
                <h2>Message preview</h2>
                <p dir="auto" style="white-space: pre-wrap; overflow-wrap: anywhere; padding: 1rem; background: var(--bg); border-radius: 8px;">{{ $announcement->message }}</p>
                @if ($announcement->include_menu_button)
                    <p style="margin-top: 0.35rem; color: var(--text-muted);">Includes an Open menu button.</p>
                @endif
            </div>
            <div>
                <h2>Audience</h2>
                <p>Unblocked students with Telegram accounts who match all of these filters.</p>
                <dl style="margin-top: 0.5rem; display: grid; grid-template-columns: max-content 1fr; gap: 0.35rem 1rem;">
                    <dt>Languages</dt>
                    <dd>{{ $announcement->languages ? implode(', ', array_intersect_key(\App\Models\Announcement::LANGUAGES, array_flip($announcement->languages))) : 'Any' }}</dd>
                    <dt>Major</dt><dd>{{ $announcement->without_program ? 'No major assigned' : 'Any' }}</dd>
                    <dt>File submissions</dt><dd>{{ $announcement->minimum_submissions > 0 ? 'At least '.$announcement->minimum_submissions : 'Any' }}</dd>
                    <dt>Joined before</dt><dd>{{ $announcement->joined_before?->format('d M Y, H:i') ?? 'Any time' }}</dd>
                </dl>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                @if (!$announcement->queued_at)
                    <form method="POST" action="{{ route('admin.announcements.queue', $announcement) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Queue announcement</button>
                    </form>
                    <a class="btn btn-secondary" href="{{ route('admin.announcements.edit', $announcement) }}">Edit draft</a>
                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete draft</button>
                    </form>
                @elseif (($counts[\App\Models\AnnouncementDelivery::PENDING] ?? 0) > 0)
                    <form method="POST" action="{{ route('admin.announcements.queue', $announcement) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Resume pending deliveries</button>
                    </form>
                @endif
                @if ($canTestSend)
                    <form method="POST" action="{{ route('admin.announcements.test', $announcement) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Send test to my Telegram</button>
                    </form>
                @else
                    <p style="color: var(--text-muted);">Link your Telegram account to send a test.</p>
                @endif
            </div>
        </div>
    </div>

    @if ($deliveries)
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Delivery results</h2>
                    <p>{{ $deliveries->total() }} recipients. Refresh to see the latest status.</p>
                </div>
            </div>
            <div style="padding: 1rem 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                @foreach (\App\Models\AnnouncementDelivery::STATUSES as $status => $label)
                    <span class="badge badge-secondary">{{ $label }} · {{ $counts[$status] ?? 0 }}</span>
                @endforeach
            </div>
            @if (($counts[\App\Models\AnnouncementDelivery::UNCONFIRMED] ?? 0) > 0)
                <p style="padding: 0 1.5rem 1rem;">Telegram did not confirm these deliveries. They are held to avoid duplicate messages.</p>
            @endif
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th scope="col">Student</th><th scope="col">Status</th><th scope="col">Sent at</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveries as $delivery)
                            <tr>
                                <td>{{ $delivery->user?->username ? '@'.$delivery->user->username : ($delivery->user?->first_name ?? 'Deleted account') }}</td>
                                <td>{{ \App\Models\AnnouncementDelivery::STATUSES[$delivery->status] }}{{ $delivery->telegram_error_code ? ' (Telegram '.$delivery->telegram_error_code.')' : '' }}</td>
                                <td>{{ $delivery->sent_at?->format('d M Y, H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('admin.partials.pagination', ['paginator' => $deliveries])
        </div>
    @endif
@endsection
