<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'parameters',
        'response_code',
        'response_size',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];
}
