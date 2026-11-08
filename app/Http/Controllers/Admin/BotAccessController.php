<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BotAccessSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BotAccessController extends Controller
{
    public function edit(Request $request, BotAccessSettings $settings): View
    {
        abort_unless($request->user()?->canManageUsers(), 403);

        $access = $settings->get();

        return view('admin.bot-access.edit', [
            'access' => $access,
            'restricted' => $settings->isRestricted($access),
            'untilLocal' => $access['restricted_until']
                ? CarbonImmutable::parse($access['restricted_until'], 'UTC')->setTimezone($access['timezone'])->format('Y-m-d\TH:i')
                : '',
        ]);
    }

    public function update(Request $request, BotAccessSettings $settings): RedirectResponse
    {
        abort_unless($request->user()?->canManageUsers(), 403);

        $data = $request->validate([
            'mode' => ['required', 'in:public,staff_only'],
            'timezone' => ['required', 'timezone'],
            'restricted_until' => ['required_if:mode,staff_only', 'nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        $until = $data['mode'] === 'staff_only'
            ? CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['restricted_until'], $data['timezone'])->utc()
            : null;

        if ($until && ! $until->isFuture()) {
            throw ValidationException::withMessages(['restricted_until' => 'Choose an expiry in the future.']);
        }

        $settings->save($data['mode'], $until, $data['timezone']);
        log_rbac('Bot access settings updated', $request->user(), [
            'mode' => $data['mode'],
            'restricted_until' => $until?->toIso8601String(),
        ]);

        return redirect()->route('admin.bot-access.edit')->with('success', 'Bot access settings saved.');
    }
}
