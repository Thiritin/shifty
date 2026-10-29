<?php

namespace App\Support;

class Branding
{
    public const ASSETS = ['logo_light', 'logo_dark', 'favicon'];

    public static function assetUrl(string $name): ?string
    {
        $version = config("shifty.assets.$name");

        return $version ? route('brand.asset', ['name' => $name, 'v' => $version]) : null;
    }

    public static function props(): array
    {
        return [
            'organization' => config('shifty.organization'),
            'logoLight' => self::assetUrl('logo_light'),
            'logoDark' => self::assetUrl('logo_dark') ?? self::assetUrl('logo_light'),
            'accentColor' => config('shifty.accent_color'),
        ];
    }

    public static function foregroundFor(string $hex): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        // ITU-R BT.601 luma weights.
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#000000' : '#ffffff';
    }
}
