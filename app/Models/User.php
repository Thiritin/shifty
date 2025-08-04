<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'remote_id',
        'shifts_expected',
        'is_admin',
        'arrival_time',
        'departure_time',
        'arrival_date',
        'departure_date',
        'has_seen_intro',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'has_seen_intro' => 'boolean',
            'arrival_date' => 'date',
            'departure_date' => 'date',
        ];
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class)->withTimestamps();
    }

    public function getShiftCountAttribute(): int
    {
        return $this->shifts()->count();
    }

    public function getDogMoodAttribute(): string
    {
        $ratio = $this->shifts_expected > 0 ? $this->shift_count / $this->shifts_expected : 0;
        
        if ($ratio >= 1) {
            return 'happy';
        } elseif ($ratio >= 0.5) {
            return 'mediocre';
        } else {
            return 'sad';
        }
    }
}
