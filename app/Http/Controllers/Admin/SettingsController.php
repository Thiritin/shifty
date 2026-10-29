<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Branding;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            ],
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
        ]);

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
