<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'start_time',
        'end_time',
        'required_people',
        'description',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    // Accessor for start_time to ensure consistent format
    public function getStartTimeAttribute($value)
    {
        if (!$value) return $value;
        // Ensure we return HH:MM format
        return substr($value, 0, 5);
    }

    // Accessor for end_time to ensure consistent format
    public function getEndTimeAttribute($value)
    {
        if (!$value) return $value;
        // Ensure we return HH:MM format
        return substr($value, 0, 5);
    }

    // Mutator for start_time to store in HH:MM:SS format
    public function setStartTimeAttribute($value)
    {
        if (!$value) {
            $this->attributes['start_time'] = null;
            return;
        }
        // If it's already HH:MM:SS, keep it, otherwise add :00
        $this->attributes['start_time'] = strlen($value) === 5 ? $value . ':00' : $value;
    }

    // Mutator for end_time to store in HH:MM:SS format
    public function setEndTimeAttribute($value)
    {
        if (!$value) {
            $this->attributes['end_time'] = null;
            return;
        }
        // If it's already HH:MM:SS, keep it, otherwise add :00
        $this->attributes['end_time'] = strlen($value) === 5 ? $value . ':00' : $value;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function getAssignedCountAttribute(): int
    {
        return $this->users()->count();
    }

    public function getSpotsAvailableAttribute(): int
    {
        return max(0, $this->required_people - $this->assigned_count);
    }

    public function getIsFullAttribute(): bool
    {
        return $this->assigned_count >= $this->required_people;
    }

    public function scopeForWeek($query, $startDate)
    {
        $endDate = $startDate->copy()->addDays(6);
        return $query->whereBetween('date', [$startDate, $endDate])->orderBy('date')->orderBy('start_time');
    }
}
