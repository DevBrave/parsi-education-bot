<?php

namespace App\Telegram\Middleware;

use App\Models\User;
use App\Telegram\Handlers\OnboardingHandler;

class CheckUserOnboarding
{
    public function __construct(
        protected OnboardingHandler $onboardingHandler
    ) {}

    /**
     * Intercept users with incomplete onboarding (missing language or degree programme).
     *
     * @return bool True if intercepted and handled, false to continue pipeline.
     */
    public function handle(array $payload, User $user): bool
    {
        $callbackData = $payload['callback_query']['data'] ?? '';
        $isOnboardingCallback = str_starts_with($callbackData, 'onboard_lang_')
            || str_starts_with($callbackData, 'onboard_prog_')
            || str_starts_with($callbackData, 'select_degree_')
            || str_starts_with($callbackData, 'select_program_')
            || str_starts_with($callbackData, 'onboard_degree_')
            || str_starts_with($callbackData, 'onboard_track_')
            || $callbackData === 'back_to_degrees'
            || $callbackData === 'onboard_back_degrees';

        // Fast-path: Fully onboarded users bypass this middleware unless tapping an onboarding callback
        if ($user->preferred_language && $user->program_id && ! $isOnboardingCallback) {
            return false;
        }

        // Delegate onboarding workflow to the OnboardingHandler
        return $this->onboardingHandler->handle($payload, $user);
    }
}
