@extends('admin.layout')

@section('title', 'Announcements')

@section('content')
    <div class="card">
        <div class="card-header">
            <h1>Announcements</h1>
            <a class="btn btn-primary" href="{{ route('admin.announcements.create') }}">New announcement</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th scope="col">Title</th><th scope="col">Status</th><th scope="col">Recipients</th></tr>
                </thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr>
                            <td><a href="{{ route('admin.announcements.show', $announcement) }}">{{ $announcement->title }}</a></td>
                            <td>{{ $announcement->queued_at ? 'Queued' : 'Draft' }}</td>
                            <td>{{ $announcement->queued_at ? $announcement->deliveries_count : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No announcements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.pagination', ['paginator' => $announcements])
    </div>
@endsection
