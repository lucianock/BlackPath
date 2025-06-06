<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'status',
        'progress',
        'wordlist',
        'status_message',
        'error',
        'started_at',
        'finished_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'progress' => 'integer'
    ];

    public function results(): HasMany
    {
        return $this->hasMany(ScanResult::class);
    }

    public function urls()
    {
        return $this->hasMany(UrlDetectada::class);
    }
}
