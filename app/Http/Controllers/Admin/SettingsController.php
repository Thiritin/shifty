<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Branding;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class SettingsController extends Controller
{
    public function edit()
    {
        return Inertia::render('Admin/Settings', [
            'settings' => [
                'app_name' => config('app.name'),
                'organization' => config('shifty.organization'),
                'default_shifts_expected' => (int) config('shifty.default_shifts_expected'),
                'self_registration' => (bool) config('shifty.self_registration'),
                'timezone' => config('shifty.timezone'),
                'accent_color' => config('shifty.accent_color'),
                'mail_mailer' => config('mail.default') === 'smtp' ? 'smtp' : 'log',
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_password_set' => filled(config('mail.mailers.smtp.password')),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
                'password_login' => (bool) config('shifty.password_login'),
                'sso_enabled' => (bool) config('shifty.sso.enabled'),
                'sso_label' => config('shifty.sso.label'),
                'sso_discovery_url' => config('services.identity.openid_configuration'),
                'sso_client_id' => config('services.identity.client_id'),
                'sso_client_secret_set' => filled(config('services.identity.client_secret')),
                'sso_scopes' => config('shifty.sso.scopes'),
                'sso_access_claim' => config('shifty.sso.access_claim'),
                'sso_access_value' => config('shifty.sso.access_value'),
                'sso_admin_claim' => config('shifty.sso.admin_claim'),
                'sso_admin_value' => config('shifty.sso.admin_value'),
                'sso_auto_create' => (bool) config('shifty.sso.auto_create'),
                'sso_link_by_email' => (bool) config('shifty.sso.link_by_email'),
            ],
            'ssoCallbackUrl' => route('auth.callback'),
            'timezones' => DateTimeZone::listIdentifiers(),
            'assets' => collect(Branding::ASSETS)->mapWithKeys(fn ($name) => [$name => Branding::assetUrl($name)]),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:60',
            'organization' => 'nullable|string|max:100',
            'default_shifts_expected' => 'required|integer|min:0|max:1000',
            'self_registration' => 'required|boolean',
            'timezone' => 'required|timezone:all',
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_light' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:1024',
            'logo_dark' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:1024',
            'favicon' => 'nullable|file|mimes:png,svg,ico|max:512',
            'remove' => 'array',
            'remove.*' => 'in:'.implode(',', Branding::ASSETS),
            'mail_mailer' => 'required|in:smtp,log',
            'mail_host' => 'nullable|required_if:mail_mailer,smtp|string|max:255',
            'mail_port' => 'nullable|required_if:mail_mailer,smtp|integer|between:1,65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|required_if:mail_mailer,smtp|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'password_login' => 'required|boolean',
            'sso_enabled' => 'required|boolean',
            'sso_label' => 'required_if_accepted:sso_enabled|nullable|string|max:60',
            'sso_discovery_url' => 'required_if_accepted:sso_enabled|nullable|url:https,http|max:500',
            'sso_client_id' => 'required_if_accepted:sso_enabled|nullable|string|max:255',
            'sso_client_secret' => 'nullable|string|max:500',
            'sso_scopes' => 'required_if_accepted:sso_enabled|nullable|string|max:255',
            'sso_access_claim' => 'nullable|required_with:sso_access_value|string|max:100',
            'sso_access_value' => 'nullable|required_with:sso_access_claim|string|max:255',
            'sso_admin_claim' => 'nullable|required_with:sso_admin_value|string|max:100',
            'sso_admin_value' => 'nullable|required_with:sso_admin_claim|string|max:255',
            'sso_auto_create' => 'required|boolean',
            'sso_link_by_email' => 'required|boolean',
        ]);

        if (! $request->boolean('password_login') && ! $request->boolean('sso_enabled')) {
            throw ValidationException::withMessages(['password_login' => 'Enable password login or single sign-on.']);
        }

        $values = [
            'app.name' => $validated['app_name'],
            'shifty.organization' => $validated['organization'] ?? null,
            'shifty.default_shifts_expected' => (int) $validated['default_shifts_expected'],
            'shifty.self_registration' => $request->boolean('self_registration'),
            'shifty.timezone' => $validated['timezone'],
            'shifty.accent_color' => $validated['accent_color'] ?? null,
            'mail.default' => $validated['mail_mailer'],
            'mail.mailers.smtp.host' => $validated['mail_host'] ?? null,
            'mail.mailers.smtp.port' => isset($validated['mail_port']) ? (int) $validated['mail_port'] : null,
            'mail.mailers.smtp.username' => $validated['mail_username'] ?? null,
            'mail.from.address' => $validated['mail_from_address'] ?? null,
            'mail.from.name' => $validated['mail_from_name'] ?? null,
        ];

        $values += [
            'shifty.password_login' => $request->boolean('password_login'),
            'shifty.sso.enabled' => $request->boolean('sso_enabled'),
            'shifty.sso.label' => $validated['sso_label'] ?? null,
            'services.identity.openid_configuration' => $validated['sso_discovery_url'] ?? null,
            'services.identity.client_id' => $validated['sso_client_id'] ?? null,
            'shifty.sso.scopes' => $validated['sso_scopes'] ?? null,
            'shifty.sso.access_claim' => $validated['sso_access_claim'] ?? null,
            'shifty.sso.access_value' => $validated['sso_access_value'] ?? null,
            'shifty.sso.admin_claim' => $validated['sso_admin_claim'] ?? null,
            'shifty.sso.admin_value' => $validated['sso_admin_value'] ?? null,
            'shifty.sso.auto_create' => $request->boolean('sso_auto_create'),
            'shifty.sso.link_by_email' => $request->boolean('sso_link_by_email'),
        ];

        if ($request->filled('sso_client_secret')) {
            $values['services.identity.client_secret'] = $validated['sso_client_secret'];
        }

        if ($request->filled('mail_password')) {
            $values['mail.mailers.smtp.password'] = $validated['mail_password'];
        }

        foreach ($validated['remove'] ?? [] as $name) {
            DB::table('brand_assets')->where('name', $name)->delete();
            $values["shifty.assets.$name"] = null;
        }

        foreach (Branding::ASSETS as $name) {
            if ($file = $request->file($name)) {
                $contents = $file->get();
                DB::table('brand_assets')->updateOrInsert(['name' => $name], [
                    'mime' => $file->getMimeType(),
                    'contents' => base64_encode($contents),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $values["shifty.assets.$name"] = substr(sha1($contents), 0, 12);
            }
        }

        Setting::put($values);

        return back()->with('success', 'Saved');
    }

    public function testMail(Request $request)
    {
        try {
            Mail::raw('This is a test message from '.config('app.name').'.', function ($message) use ($request) {
                $message->to($request->user()->email)->subject(config('app.name').' test mail');
            });
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Test mail sent to '.$request->user()->email);
    }
}
