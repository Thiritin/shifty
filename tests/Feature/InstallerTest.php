<?php

namespace Tests\Feature;

use App\Http\Controllers\InstallController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'token' => InstallController::token(),
            'app_name' => 'Crew Planner',
            'organization' => 'Example Con',
            'name' => 'Owner',
            'email' => 'owner@example.org',
            'password' => 'long-enough',
            'password_confirmation' => 'long-enough',
            ...$overrides,
        ];
    }

    public function test_uninstalled_apps_send_every_page_to_the_installer()
    {
        config(['shifty.installed' => false]);

        $this->get('/login')->assertRedirect('/install');
        $this->get('/install')->assertOk();
    }

    public function test_installer_rejects_a_wrong_token()
    {
        config(['shifty.installed' => false]);

        $this->post('/install', $this->payload(['token' => 'nope']))->assertSessionHasErrors('token');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_installer_creates_the_admin_and_closes_itself()
    {
        config(['shifty.installed' => false]);

        $this->post('/install', $this->payload())->assertRedirect('/admin/settings');

        $admin = User::where('email', 'owner@example.org')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertAuthenticatedAs($admin);

        $this->get('/install')->assertNotFound();
        $this->get('/admin/settings')->assertOk();
    }

    public function test_installer_is_hidden_once_installed()
    {
        $this->get('/install')->assertNotFound();
        $this->post('/install', $this->payload())->assertNotFound();
    }

    public function test_missing_app_key_shows_the_setup_page()
    {
        config(['app.key' => '']);

        $this->get('/login')->assertStatus(503)->assertSee('APP_KEY=base64:', false);
    }

    public function test_missing_sqlite_file_shows_the_database_setup_page()
    {
        config(['database.connections.sqlite.database' => storage_path('framework/testing/missing.sqlite')]);

        $this->get('/login')->assertStatus(503)->assertSee('DB_CONNECTION=mysql');
    }

    public function test_unreachable_database_shows_the_error_page()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => 1,
        ]);

        $this->get('/login')->assertStatus(503)->assertSee('The database is not reachable')->assertSee('127.0.0.1:1');

        config(['database.default' => 'sqlite']);
    }
}
