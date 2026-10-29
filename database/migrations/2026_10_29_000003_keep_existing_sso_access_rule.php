<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Before settings existed, login was hardcoded to this group; keep it for installs that already have SSO users.
    public function up(): void
    {
        if (DB::table('users')->whereNotNull('remote_id')->doesntExist()) {
            return;
        }

        Setting::put([
            'shifty.sso.enabled' => true,
            'shifty.sso.scopes' => 'openid profile email groups',
            'shifty.sso.access_claim' => 'groups',
            'shifty.sso.access_value' => 'RD01V78YL72JLKOZ',
        ]);
    }
};
