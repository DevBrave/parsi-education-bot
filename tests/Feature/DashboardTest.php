<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('website-login')]
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_visit_the_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_caches_heavy_metrics_and_supports_refresh()
    {
        Cache::forget('admin:dashboard:heavy_metrics');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // First visit populates cache
        $this->assertFalse(Cache::has('admin:dashboard:heavy_metrics'));
        $response1 = $this->get(route('admin.dashboard'));
        $response1->assertOk();
        $this->assertTrue(Cache::has('admin:dashboard:heavy_metrics'));

        // Visiting with ?refresh=1 refreshes the cache
        $response2 = $this->get(route('admin.dashboard', ['refresh' => 1]));
        $response2->assertOk();
        $this->assertTrue(Cache::has('admin:dashboard:heavy_metrics'));
    }
}
