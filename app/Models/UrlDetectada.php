<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrlDetectada extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'url',
        'http_code'
    ];

    public function scan()
    {
        return $this->belongsTo(Scan::class);
    }
}
