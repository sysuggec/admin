<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 't_operation_log';

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
        'module',
        'action',
        'title',
        'method',
        'url',
        'params',
        'response',
        'ip',
        'user_agent',
        'status',
        'error_msg',
        'duration',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status' => 'integer',
            'duration' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
