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

    /**
     * Check if this is a grouped import
     */
    public function isGroupedImport(): bool
    {
        return ($this->import_settings['is_grouped_import'] ?? false) === true;
    }

    /**
     * Get the loan types for this grouped import
     */
    public function getLoanTypes(): array
    {
        return $this->import_settings['loan_types'] ?? [];
    }

    /**
     * Get the total number of loan types in this grouped import
     */
    public function getLoanTypeCount(): int
    {
        return count($this->getLoanTypes());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function connection()
    {
        return $this->belongsTo(DatabaseConnection::class);
    }

}
