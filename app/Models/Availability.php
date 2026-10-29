<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One hour block a user marked, starting at `starts_at`. A missing row means no preference.
 */
class Availability extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'starts_at', 'status'];

    protected $casts = ['starts_at' => 'datetime'];
}
