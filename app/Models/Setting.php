<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use PDOException;

/**
 * Keys are config keys, e.g. `mail.from.address`.
 */
class Setting extends Model
{
    public const ENCRYPTED = ['mail.mailers.smtp.password'];

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'json'];

    /** @var array<string, mixed> config values from before the first override */
    private static array $defaults = [];

    public static function apply(): void
    {
        try {
            $values = Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());
        } catch (QueryException|PDOException|SQLiteDatabaseDoesNotExistException) {
            return;
        }

        // Long-lived processes keep config between requests, so deleted overrides must be undone.
        foreach (self::$defaults as $key => $value) {
            config([$key => $value]);
        }

        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::$defaults)) {
                self::$defaults[$key] = config($key);
            }

            if (in_array($key, self::ENCRYPTED, true)) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (DecryptException) {
                    continue;
                }
            }

            config([$key => $value]);
        }
    }

    // A null or empty value deletes the override so the config default applies again.
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                static::whereKey($key)->delete();

                continue;
            }

            static::updateOrCreate(['key' => $key], [
                'value' => in_array($key, self::ENCRYPTED, true) ? Crypt::encryptString($value) : $value,
            ]);
        }

        Cache::forget('settings');
    }
}
