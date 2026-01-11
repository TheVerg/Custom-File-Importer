<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'connection_id',
        'database_name',
        'table_name',
        'file_name',
        'file_path',
        'file_type',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'column_mappings',
        'import_settings',
        'status',
        'error_message',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'column_mappings' => 'array',
        'import_settings' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function connection()
    {
        return $this->belongsTo(DatabaseConnection::class);
    }

}
