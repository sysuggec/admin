<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 't_login_log';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'username',
        'ip',
        'user_agent',
        'login_time',
        'status',
        'message',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'login_time' => 'datetime',
            'status' => 'integer',
        ];
    }
}
