<?php

namespace Database\Seeders;

use App\Models\DatabaseConnection;
use Illuminate\Database\Seeder;

class DatabaseConnectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $connections = [
            [
                'name' => 'Local MySQL',
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'dupleix',
                'username' => 'root',
                'password' => '',
                'is_active' => true,
            ],
            [
                'name' => 'Local PostgreSQL',
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'postgres',
                'username' => 'postgres',
                'password' => 'postgres',
                'is_active' => true,
            ],
            [
                'name' => 'SQLite Default',
                'driver' => 'sqlite',
                'database' => database_path('database.sqlite'),
                'is_active' => true,
            ]
        ];

        foreach ($connections as $connection) {
            DatabaseConnection::updateOrCreate(
                ['name' => $connection['name']],
                $connection
            );
        }
    }
}
