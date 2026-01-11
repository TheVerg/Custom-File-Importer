<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatabaseConnection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password',
        'options',
        'is_active'
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'port' => 'integer'
    ];

    public function importJobs()
    {
        return $this->hasMany(ImportJob::class);
    }

    public function getConnectionString(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'options' => $this->options ?? [],
        ];
    }
}
