@extends('admin.layout')

@section('title', 'Live System Logs & Monitoring')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.25rem;">

    <!-- Top Header & Stats -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>📡 Live System Logs</span>
                <span id="liveStatusBadge" style="font-size: 0.75rem; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.2rem 0.6rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 700;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block; animation: pulse 2s infinite;"></span>
                    AUTO-POLLING (3s)
                </span>
            </h1>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0 0;">
                Smart short-polling telemetry across background workers, Telegram bot webhooks, and Google Drive sync.
            </p>
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
            <a href="{{ route('admin.logs.download', ['channel' => $activeChannel]) }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.35rem; text-decoration: none;">
                📥 Download Log
            </a>

            @if(Auth::user()->isAdmin())
                <form action="{{ route('admin.logs.clear', ['channel' => $activeChannel]) }}" method="POST" onsubmit="return confirm('⚠️ Are you sure you want to permanently clear the log file for {{ $channels[$activeChannel]['name'] }}?');" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                        🗑️ Purge File
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Channel Selector Tabs -->
    <div style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.25rem; border-bottom: 1px solid var(--border);">
        @foreach($channels as $key => $meta)
            @php
                $isActive = ($activeChannel === $key);
                $stat = $channelStats[$key] ?? null;
            @endphp
            <a href="{{ route('admin.logs.index', ['channel' => $key]) }}" 
               style="text-decoration: none; padding: 0.55rem 0.85rem; border-radius: 8px; font-size: 0.825rem; font-weight: {{ $isActive ? '700' : '600' }}; display: inline-flex; align-items: center; gap: 0.4rem; white-space: nowrap; transition: all 0.15s ease;
                      background: {{ $isActive ? '#1e293b' : 'transparent' }};
                      color: {{ $isActive ? '#f8fafc' : 'var(--text-muted)' }};
                      border: 1px solid {{ $isActive ? '#334155' : 'transparent' }};">
                <span>{{ $meta['icon'] }}</span>
                <span>{{ $meta['name'] }}</span>
                @if($stat && $stat['bytes'] > 0)
                    <span style="font-size: 0.7rem; padding: 0.1rem 0.4rem; border-radius: 9999px; background: {{ $isActive ? '#0f172a' : 'var(--border)' }}; color: {{ $isActive ? '#94a3b8' : 'var(--text-muted)' }};">
                        {{ $stat['size'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    <!-- Terminal Control Toolbar -->
    <div style="background: #0f172a; border: 1px solid #1e293b; border-bottom: none; border-radius: 10px 10px 0 0; padding: 0.75rem 1rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem;">
        
        <!-- Left: Level Filters -->
        <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 700; margin-right: 0.25rem;">LEVEL:</span>
            
            <button type="button" class="level-filter-btn active" data-level="ALL" style="background: #334155; color: white; border: none; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                ALL
            </button>
            <button type="button" class="level-filter-btn" data-level="ERROR" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                ERROR / CRITICAL
            </button>
            <button type="button" class="level-filter-btn" data-level="WARNING" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                WARNING
            </button>
            <button type="button" class="level-filter-btn" data-level="INFO" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                INFO
            </button>
            <button type="button" class="level-filter-btn" data-level="DEBUG" style="background: rgba(96, 165, 250, 0.15); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.3); padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                DEBUG
            </button>
        </div>

        <!-- Right: Search, Stream Pause, Auto-scroll, Clear View -->
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter text input -->
            <div style="position: relative;">
                <input type="text" id="logSearchInput" placeholder="Filter text..." style="background: #1e293b; border: 1px solid #334155; color: #f8fafc; font-size: 0.775rem; padding: 0.3rem 0.6rem 0.3rem 1.75rem; border-radius: 6px; width: 160px; outline: none;">
                <span style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: #64748b;">🔍</span>
            </div>

            <!-- Auto-scroll toggle -->
            <button type="button" id="toggleScrollBtn" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
                <span id="scrollIcon">⬇️</span> Auto-scroll: <span id="scrollStatus" style="color: #10b981;">ON</span>
            </button>

            <!-- Manual refresh button -->
            <button type="button" id="refreshNowBtn" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;" title="Fetch latest logs right now">
                <span id="refreshIcon">🔄</span> Refresh
            </button>

            <!-- Stream pause/play toggle -->
            <button type="button" id="toggleStreamBtn" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
                <span id="streamIcon">⏸️</span> Pause
            </button>

            <!-- Clear visual console -->
            <button type="button" id="clearConsoleBtn" style="background: #1e293b; color: #94a3b8; border: 1px solid #334155; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.75rem; cursor: pointer;" title="Clear console view (does not delete file)">
                Clear View
            </button>
        </div>
    </div>

    <!-- Terminal Window -->
    <div style="background: #090d16; border: 1px solid #1e293b; border-radius: 0 0 10px 10px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);">
        
        <!-- Terminal Sub-Header -->
        <div style="background: #0f172a; border-bottom: 1px solid #1e293b; padding: 0.4rem 1rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.725rem; color: #64748b; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span>📁 <strong style="color: #94a3b8;">{{ basename($logPath) }}</strong> ({{ $fileSizeFormatted }})</span>
                <span>•</span>
                <span>Channel: <span style="color: #38bdf8;">{{ $channels[$activeChannel]['name'] }}</span></span>
            </div>
            <div>
                Showing <span id="displayedCount" style="color: #f8fafc; font-weight: 700;">{{ count($initialLines) }}</span> lines
            </div>
        </div>

        <!-- Terminal Output Screen -->
        <div id="terminalContainer" style="height: 580px; overflow-y: auto; padding: 1rem; font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.8rem; line-height: 1.6; color: #e2e8f0;">
            
            <div id="logLinesList" style="display: flex; flex-direction: column; gap: 0.25rem;">
                @forelse($initialLines as $entry)
                    @php
                        $level = $entry['level'];
                        $levelColor = match($level) {
                            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => '#ef4444',
                            'WARNING' => '#f59e0b',
                            'NOTICE', 'INFO' => '#10b981',
                            'DEBUG' => '#60a5fa',
                            default => '#94a3b8'
                        };
                    @endphp
                    <div class="log-line-row" data-level="{{ $level }}" style="display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.15rem 0.35rem; border-radius: 4px; transition: background 0.1s ease;">
                        @if($entry['date'])
                            <span style="color: #64748b; font-size: 0.725rem; white-space: nowrap; flex-shrink: 0;">
                                {{ $entry['date'] }}
                            </span>
                        @endif

                        @if($level !== 'TRACE')
                            <span style="color: {{ $levelColor }}; font-weight: 700; font-size: 0.725rem; padding: 0.05rem 0.35rem; border-radius: 3px; background: rgba(255, 255, 255, 0.05); flex-shrink: 0; min-width: 55px; text-align: center;">
                                {{ $level }}
                            </span>
                        @else
                            <span style="color: #475569; font-size: 0.725rem; min-width: 55px; text-align: center; flex-shrink: 0;">
                                ↳
                            </span>
                        @endif

                        <div style="flex-grow: 1; word-break: break-word;">
                            <span style="color: {{ $level === 'ERROR' ? '#fca5a5' : ($level === 'WARNING' ? '#fde68a' : '#f1f5f9') }};">
                                {{ $entry['message'] }}
                            </span>

                            @if($entry['context'])
                                <details style="margin-top: 0.25rem;">
                                    <summary style="cursor: pointer; color: #818cf8; font-size: 0.725rem; user-select: none;">
                                        📦 Context Payload
                                    </summary>
                                    <pre style="background: #0b1120; border: 1px solid #1e293b; padding: 0.5rem; border-radius: 4px; font-size: 0.725rem; color: #c7d2fe; margin-top: 0.25rem; overflow-x: auto; white-space: pre-wrap;">{{ json_encode($entry['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif
                        </div>
                    </div>
                @empty
                    <div id="noLogsPlaceholder" style="text-align: center; padding: 3rem 1rem; color: #64748b;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📜</div>
                        <div>No log entries recorded in this channel yet.</div>
                        <div style="font-size: 0.75rem; color: #475569; margin-top: 0.25rem;">Waiting for incoming events...</div>
                    </div>
                @endforelse
            </div>

        </div>

    </div>

</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.9); }
}

#terminalContainer::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
#terminalContainer::-webkit-scrollbar-track {
    background: #090d16;
}
#terminalContainer::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 4px;
}
#terminalContainer::-webkit-scrollbar-thumb:hover {
    background: #334155;
}

.log-line-row:hover {
    background: rgba(255, 255, 255, 0.03);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pollUrl = "{{ route('admin.logs.poll', ['channel' => $activeChannel]) }}";
    const terminal = document.getElementById('terminalContainer');
    const logList = document.getElementById('logLinesList');
    const displayedCountElem = document.getElementById('displayedCount');
    const toggleScrollBtn = document.getElementById('toggleScrollBtn');
    const scrollStatus = document.getElementById('scrollStatus');
    const toggleStreamBtn = document.getElementById('toggleStreamBtn');
    const streamIcon = document.getElementById('streamIcon');
    const clearConsoleBtn = document.getElementById('clearConsoleBtn');
    const searchInput = document.getElementById('logSearchInput');
    const levelBtns = document.querySelectorAll('.level-filter-btn');
    const liveStatusBadge = document.getElementById('liveStatusBadge');
    const refreshNowBtn = document.getElementById('refreshNowBtn');

    let isAutoScroll = true;
    let isPollingActive = true;
    let activeLevelFilter = 'ALL';
    let searchQuery = '';
    let lastPos = {{ $fileSizeBytes }};
    let isFetching = false;
    let pollInterval = null;

    // Scroll to bottom on load
    scrollToBottom();

    function scrollToBottom() {
        if (isAutoScroll) {
            terminal.scrollTop = terminal.scrollHeight;
        }
    }

    // Toggle Auto-scroll
    toggleScrollBtn.addEventListener('click', function () {
        isAutoScroll = !isAutoScroll;
        scrollStatus.textContent = isAutoScroll ? 'ON' : 'OFF';
        scrollStatus.style.color = isAutoScroll ? '#10b981' : '#ef4444';
        if (isAutoScroll) scrollToBottom();
    });

    // Detect user manual scroll up
    terminal.addEventListener('scroll', function () {
        const atBottom = terminal.scrollHeight - terminal.scrollTop <= terminal.clientHeight + 50;
        if (!atBottom && isAutoScroll) {
            isAutoScroll = false;
            scrollStatus.textContent = 'OFF';
            scrollStatus.style.color = '#ef4444';
        }
    });

    // Smart Short-Polling Fetcher
    async function fetchLogs() {
        if (isFetching || !isPollingActive) return;
        isFetching = true;

        const refreshIcon = document.getElementById('refreshIcon');
        if (refreshIcon) refreshIcon.style.opacity = '0.4';

        try {
            const response = await fetch(`${pollUrl}?since_pos=${lastPos}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) return;

            const data = await response.json();

            if (data.truncated) {
                lastPos = 0;
                logList.innerHTML = '';
            }

            if (data.lines && data.lines.length > 0) {
                const placeholder = document.getElementById('noLogsPlaceholder');
                if (placeholder) placeholder.remove();

                data.lines.forEach(entry => appendLogLine(entry));
                scrollToBottom();
                updateLineCount();
            }

            if (data.new_pos !== undefined) {
                lastPos = data.new_pos;
            }
        } catch (err) {
            console.warn('Log poll error:', err);
        } finally {
            isFetching = false;
            if (refreshIcon) refreshIcon.style.opacity = '1';
        }
    }

    function startPolling() {
        isPollingActive = true;
        streamIcon.textContent = '⏸️';
        toggleStreamBtn.childNodes[2].textContent = ' Pause';
        liveStatusBadge.innerHTML = '<span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block; animation: pulse 2s infinite;"></span> AUTO-POLLING (3s)';
        liveStatusBadge.style.background = 'rgba(16, 185, 129, 0.15)';
        liveStatusBadge.style.color = '#10b981';
        liveStatusBadge.style.borderColor = 'rgba(16, 185, 129, 0.3)';

        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            // Only poll if tab is currently visible and polling is active
            if (!document.hidden && isPollingActive) {
                fetchLogs();
            }
        }, 3000);
    }

    function stopPolling() {
        isPollingActive = false;
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
        streamIcon.textContent = '▶️';
        toggleStreamBtn.childNodes[2].textContent = ' Resume';
        liveStatusBadge.innerHTML = '<span style="width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span> PAUSED';
        liveStatusBadge.style.background = 'rgba(148, 163, 184, 0.15)';
        liveStatusBadge.style.color = '#94a3b8';
        liveStatusBadge.style.borderColor = 'rgba(148, 163, 184, 0.3)';
    }

    // Toggle button click
    toggleStreamBtn.addEventListener('click', function () {
        if (isPollingActive) {
            stopPolling();
        } else {
            startPolling();
            fetchLogs();
        }
    });

    // Manual Refresh button click
    if (refreshNowBtn) {
        refreshNowBtn.addEventListener('click', function () {
            fetchLogs();
        });
    }

    // Immediately poll on tab switch / window focus
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && isPollingActive) {
            fetchLogs();
        }
    });

    // Start polling automatically
    startPolling();

    // Append new incoming line to DOM
    function appendLogLine(entry) {
        const row = document.createElement('div');
        row.className = 'log-line-row';
        row.setAttribute('data-level', entry.level || 'INFO');
        row.style.display = 'flex';
        row.style.alignItems = 'flex-start';
        row.style.gap = '0.65rem';
        row.style.padding = '0.15rem 0.35rem';
        row.style.borderRadius = '4px';

        let levelColor = '#94a3b8';
        if (['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'].includes(entry.level)) levelColor = '#ef4444';
        else if (entry.level === 'WARNING') levelColor = '#f59e0b';
        else if (['NOTICE', 'INFO'].includes(entry.level)) levelColor = '#10b981';
        else if (entry.level === 'DEBUG') levelColor = '#60a5fa';

        let dateHtml = entry.date ? `<span style="color: #64748b; font-size: 0.725rem; white-space: nowrap; flex-shrink: 0;">${escapeHtml(entry.date)}</span>` : '';
        let badgeHtml = entry.level !== 'TRACE'
            ? `<span style="color: ${levelColor}; font-weight: 700; font-size: 0.725rem; padding: 0.05rem 0.35rem; border-radius: 3px; background: rgba(255, 255, 255, 0.05); flex-shrink: 0; min-width: 55px; text-align: center;">${escapeHtml(entry.level)}</span>`
            : `<span style="color: #475569; font-size: 0.725rem; min-width: 55px; text-align: center; flex-shrink: 0;">↳</span>`;

        let msgColor = entry.level === 'ERROR' ? '#fca5a5' : (entry.level === 'WARNING' ? '#fde68a' : '#f1f5f9');
        let contextHtml = '';
        if (entry.context) {
            contextHtml = `
                <details style="margin-top: 0.25rem;">
                    <summary style="cursor: pointer; color: #818cf8; font-size: 0.725rem; user-select: none;">📦 Context Payload</summary>
                    <pre style="background: #0b1120; border: 1px solid #1e293b; padding: 0.5rem; border-radius: 4px; font-size: 0.725rem; color: #c7d2fe; margin-top: 0.25rem; overflow-x: auto; white-space: pre-wrap;">${escapeHtml(JSON.stringify(entry.context, null, 2))}</pre>
                </details>
            `;
        }

        row.innerHTML = `
            ${dateHtml}
            ${badgeHtml}
            <div style="flex-grow: 1; word-break: break-word;">
                <span style="color: ${msgColor};">${escapeHtml(entry.message)}</span>
                ${contextHtml}
            </div>
        `;

        // Apply active filter
        if (!shouldShowRow(entry.level, entry.raw)) {
            row.style.display = 'none';
        }

        logList.appendChild(row);

        // Keep maximum 800 lines in DOM to protect browser memory
        while (logList.children.length > 800) {
            logList.removeChild(logList.firstChild);
        }
    }

    // Filter by Level
    levelBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            levelBtns.forEach(b => {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.borderColor = 'transparent';
                b.style.color = '#94a3b8';
            });

            this.classList.add('active');
            activeLevelFilter = this.getAttribute('data-level');
            
            // Highlight active button
            this.style.background = '#334155';
            this.style.color = 'white';
            this.style.borderColor = '#475569';

            applyClientFilters();
        });
    });

    // Filter by Text Search
    searchInput.addEventListener('input', function () {
        searchQuery = this.value.toLowerCase().trim();
        applyClientFilters();
    });

    function applyClientFilters() {
        const rows = document.querySelectorAll('.log-line-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const level = row.getAttribute('data-level');
            const text = row.textContent.toLowerCase();

            if (shouldShowRow(level, text)) {
                row.style.display = 'flex';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        displayedCountElem.textContent = visibleCount;
    }

    function shouldShowRow(level, fullText) {
        // Level check
        if (activeLevelFilter !== 'ALL') {
            if (activeLevelFilter === 'ERROR' && !['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'].includes(level)) return false;
            if (activeLevelFilter === 'WARNING' && level !== 'WARNING') return false;
            if (activeLevelFilter === 'INFO' && !['NOTICE', 'INFO'].includes(level)) return false;
            if (activeLevelFilter === 'DEBUG' && level !== 'DEBUG') return false;
        }

        // Text check
        if (searchQuery && !fullText.includes(searchQuery)) {
            return false;
        }

        return true;
    }

    // Clear console view
    clearConsoleBtn.addEventListener('click', function () {
        logList.innerHTML = '<div id="noLogsPlaceholder" style="text-align: center; padding: 3rem 1rem; color: #64748b;"><div style="font-size: 2rem; margin-bottom: 0.5rem;">🧹</div><div>Console cleared. Live events will appear below as they arrive.</div></div>';
        displayedCountElem.textContent = '0';
    });

    function updateLineCount() {
        const visible = logList.querySelectorAll('.log-line-row:not([style*="display: none"])');
        displayedCountElem.textContent = visible.length;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Start streaming
    startStream();
});
</script>
@endsection
