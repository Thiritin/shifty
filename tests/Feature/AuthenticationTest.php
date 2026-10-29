<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function enableSso(array $sso = []): void
    {
        config([
            'services.identity.openid_configuration' => 'https://id.example.org/.well-known/openid-configuration',
            'services.identity.client_id' => 'client',
            'shifty.sso' => [...config('shifty.sso'), 'enabled' => true, ...$sso],
        ]);
    }

    private function ssoReturns(array $raw): void
    {
        $identity = (new SocialiteUser)->setRaw($raw)->map([
            'id' => $raw['sub'],
            'email' => $raw['email'] ?? null,
            'name' => $raw['name'] ?? null,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($identity);
        Socialite::shouldReceive('driver')->with('identity')->andReturn($provider);
    }

    public function test_users_can_log_in_with_a_password()
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_or_missing_passwords_are_rejected()
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $ssoOnly = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $ssoOnly->email, 'password' => 'anything'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_password_login_can_be_disabled()
    {
        config(['shifty.password_login' => false]);
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])->assertNotFound();
    }

    public function test_sso_routes_are_hidden_until_configured()
    {
        config(['shifty.sso.enabled' => false]);

        $this->get('/auth/login')->assertNotFound();
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->component('auth/Login')->where('sso', null));
    }

    public function test_sso_creates_a_user_and_keeps_admin_set_values_on_later_logins()
    {
        $this->enableSso();
        $this->ssoReturns(['sub' => 'abc', 'email' => 'fox@example.org', 'name' => 'Fox']);

        $this->get('/auth/callback')->assertRedirect('/dashboard');
        $user = User::where('remote_id', 'abc')->firstOrFail();
        $user->update(['shifts_expected' => 7]);

        auth()->logout();
        $this->get('/auth/callback');
        $this->assertSame(7, $user->fresh()->shifts_expected);
    }

    public function test_sso_access_rule_rejects_users_without_the_claim()
    {
        $this->enableSso(['access_claim' => 'groups', 'access_value' => 'staff']);
        $this->ssoReturns(['sub' => 'abc', 'email' => 'fox@example.org', 'groups' => ['guests']]);

        $this->get('/auth/callback')->assertRedirect('/login')->assertSessionHasErrors('sso');
        $this->assertGuest();
    }

    public function test_sso_admin_rule_grants_admin()
    {
        $this->enableSso(['admin_claim' => 'roles', 'admin_value' => 'lead']);
        $this->ssoReturns(['sub' => 'abc', 'email' => 'fox@example.org', 'roles' => 'member lead']);

        $this->get('/auth/callback');
        $this->assertTrue(User::where('remote_id', 'abc')->value('is_admin'));
    }

    public function test_sso_does_not_take_over_an_existing_email_unless_linking_is_on()
    {
        $local = User::factory()->create(['email' => 'fox@example.org', 'remote_id' => null]);
        $this->ssoReturns(['sub' => 'abc', 'email' => 'fox@example.org']);

        $this->enableSso();
        $this->get('/auth/callback')->assertSessionHasErrors('sso');
        $this->assertNull($local->fresh()->remote_id);

        $this->enableSso(['link_by_email' => true]);
        $this->get('/auth/callback')->assertRedirect('/dashboard');
        $this->assertSame('abc', $local->fresh()->remote_id);
    }

    public function test_sso_refuses_unknown_users_when_auto_create_is_off()
    {
        $this->enableSso(['auto_create' => false]);
        $this->ssoReturns(['sub' => 'abc', 'email' => 'fox@example.org']);

        $this->get('/auth/callback')->assertSessionHasErrors('sso');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_debug_login_requires_local_env_debug_and_loopback()
    {
        $user = User::factory()->create();

        $this->post('/login/debug', ['user_id' => $user->id])->assertNotFound();

        $this->app['env'] = 'local';
        config(['app.debug' => true]);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->withHeader('X-Forwarded-For', '203.0.113.9')
            ->post('/login/debug', ['user_id' => $user->id])->assertNotFound();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->post('/login/debug', ['user_id' => $user->id])->assertNotFound();
        $this->assertGuest();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->withHeaders(['X-Forwarded-For' => ''])
            ->post('/login/debug', ['user_id' => $user->id])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admins_can_create_users_who_can_then_log_in()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Otter',
            'email' => 'otter@example.org',
            'password' => 'long-enough',
            'shifts_expected' => 2,
            'is_admin' => false,
        ])->assertSessionHasNoErrors();

        auth()->logout();
        $this->post('/login', ['email' => 'otter@example.org', 'password' => 'long-enough'])->assertRedirect('/dashboard');
    }

    public function test_admins_cannot_remove_their_own_admin_access()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'shifts_expected' => 0,
            'is_admin' => false,
        ])->assertSessionHasErrors('is_admin');
    }

    public function test_users_can_change_their_password()
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'wrong',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(password_verify('new-password', $user->fresh()->password));
    }

    public function test_break_glass_command_creates_an_admin_and_enables_password_login()
    {
        config(['shifty.password_login' => false]);

        $this->artisan('shifty:admin', ['email' => 'root@example.org'])
            ->expectsQuestion('Password (leave empty to generate one)', 'break-glass-pw')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'root@example.org')->value('is_admin'));
        $this->assertDatabaseHas('settings', ['key' => 'shifty.password_login']);
    }

    public function test_upgrade_keeps_the_old_group_restriction_only_for_existing_sso_installs()
    {
        $migration = require database_path('migrations/2026_10_29_000003_keep_existing_sso_access_rule.php');

        $migration->up();
        $this->assertDatabaseMissing('settings', ['key' => 'shifty.sso.access_value']);

        User::factory()->create(['remote_id' => 'legacy']);
        $migration->up();
        $this->assertDatabaseHas('settings', ['key' => 'shifty.sso.access_value']);
    }
}
