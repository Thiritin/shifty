<?php

namespace App\Http\Controllers;

use App\Support\Branding;
use Illuminate\Support\Facades\DB;

class BrandAssetController extends Controller
{
    public function __invoke(string $name)
    {
        abort_unless(in_array($name, Branding::ASSETS, true), 404);

        $asset = DB::table('brand_assets')->where('name', $name)->first();
        abort_unless($asset, 404);

        return response(base64_decode($asset->contents), 200, [
            'Content-Type' => $asset->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            // Uploaded SVGs can carry scripts; this keeps them inert when opened directly.
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
