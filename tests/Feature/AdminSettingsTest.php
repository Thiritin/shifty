<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'app_name' => 'Crew Planner',
            'organization' => 'Example Con',
            'default_shifts_expected' => 3,
            'self_registration' => true,
            'timezone' => 'Europe/Berlin',
            'accent_color' => '#ff6600',
            'mail_mailer' => 'log',
            'password_login' => true,
            'sso_enabled' => false,
            'sso_auto_create' => true,
            'sso_link_by_email' => false,
            ...$overrides,
        ];
    }

    private function shift(): Shift
    {
        return Shift::create([
            'name' => 'Door',
            'date' => '2026-08-20',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'required_people' => 2,
        ]);
    }

    public function test_non_admins_cannot_open_settings()
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_saved_settings_apply_on_the_next_request()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/settings', $this->payload())
            ->assertSessionHasNoErrors();

        $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('name', 'Crew Planner')
            ->where('branding.organization', 'Example Con')
            ->where('branding.accentColor', '#ff6600')
            ->where('settings.timezone', 'Europe/Berlin')
            ->where('settings.default_shifts_expected', 3)
        );
    }

    public function test_clearing_a_value_falls_back_to_the_default()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post('/admin/settings', $this->payload());
        $this->actingAs($admin)->post('/admin/settings', $this->payload(['organization' => '', 'accent_color' => '']));

        $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('branding.organization', null)
            ->where('branding.accentColor', null)
        );
    }

    public function test_uploaded_logo_is_served_with_a_restrictive_csp()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect width="1" height="1"/></svg>';

        $this->actingAs($admin)->post('/admin/settings', $this->payload([
            'logo_light' => UploadedFile::fake()->createWithContent('logo.svg', $svg),
        ]))->assertSessionHasNoErrors();

        $url = null;
        $this->get('/admin/settings')->assertInertia(function (Assert $page) use (&$url) {
            $url = $page->toArray()['props']['branding']['logoLight'];
            $page->where('branding.logoDark', $url);
        });

        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'")
            ->assertContent($svg);

        $this->actingAs($admin)->post('/admin/settings', $this->payload(['remove' => ['logo_light']]));
        $this->get($url)->assertNotFound();
    }

    public function test_disabling_self_registration_blocks_users_but_not_admins()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $shift = $this->shift();

        $this->actingAs($admin)->post('/admin/settings', $this->payload(['self_registration' => false]));

        $this->actingAs($user)->post("/shifts/{$shift->id}/assign")->assertForbidden();
        $this->actingAs($user)->delete("/shifts/{$shift->id}/unassign")->assertForbidden();

        $this->actingAs($admin)->post("/shifts/{$shift->id}/assign/{$user->id}")->assertRedirect();
        $this->assertTrue($shift->users()->whereKey($user->id)->exists());
    }

    public function test_users_can_sign_up_while_self_registration_is_on()
    {
        $user = User::factory()->create();
        $shift = $this->shift();

        $this->actingAs($user)->post("/shifts/{$shift->id}/assign")->assertRedirect();
        $this->assertTrue($shift->users()->whereKey($user->id)->exists());
    }
}
