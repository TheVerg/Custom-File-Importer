<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\DatabaseConnection;
use PDO;
use PDOException;

class DatabaseService
{
    public function getDatabases(DatabaseConnection $connection): array
    {
        try {
            $tempConnection = 'temp_' . $connection->id . '_' . time();
            
            // Build connection configuration
            $config = [
                'driver' => $connection->driver,
                'host' => $connection->host,
                'port' => $connection->port,
                'username' => $connection->username,
                'password' => $connection->password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'strict' => false,
            ];
            
            // For MySQL, connect to information_schema to get all databases
            if ($connection->driver === 'mysql') {
                $config['database'] = 'information_schema';
            } else {
                $config['database'] = $connection->database ?: 'postgres';
            }
            
            \Log::info('Setting up database connection', [
                'temp_connection' => $tempConnection,
                'driver' => $connection->driver,
                'host' => $connection->host
            ]);
            
            // Set temporary connection
            Config::set("database.connections.{$tempConnection}", $config);
            
            // Get databases based on driver
            if ($connection->driver === 'mysql') {
                // Method 1: SHOW DATABASES (most reliable)
                $databases = $this->getMysqlDatabases($tempConnection);
            } elseif ($connection->driver === 'pgsql') {
                $databases = $this->getPgsqlDatabases($tempConnection);
            } elseif ($connection->driver === 'sqlite') {
                $databases = $this->getSqliteDatabases($connection);
            } else {
                throw new \Exception("Unsupported database driver: {$connection->driver}");
            }
            
            // Clean up temporary connection
            Config::set("database.connections.{$tempConnection}", null);
            
            // Filter out system databases
            $databases = array_filter($databases, function($db) use ($connection) {
                if ($connection->driver === 'mysql') {
                    return !in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys']);
                } elseif ($connection->driver === 'pgsql') {
                    return !in_array($db, ['postgres', 'template0', 'template1']);
                }
                return true;
            });
            
            \Log::info('Successfully fetched databases', [
                'count' => count($databases),
                'driver' => $connection->driver
            ]);
            
            return array_values($databases);
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch databases', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'driver' => $connection->driver,
                'host' => $connection->host
            ]);
            
            throw new \Exception("Failed to fetch databases: " . $e->getMessage());
        }
    }
    
    private function getMysqlDatabases(string $connectionName): array
    {
        \Log::debug('Fetching MySQL databases');
        
        try {
            // Try SHOW DATABASES first (most reliable)
            $results = DB::connection($connectionName)->select('SHOW DATABASES');
            
            $databases = [];
            foreach ($results as $row) {
                // Convert object to array to handle dynamic property names
                $rowArray = (array)$row;
                
                // Try to get database name from possible column names
                $dbName = null;
                
                // Common column names from SHOW DATABASES
                if (isset($rowArray['Database'])) {
                    $dbName = $rowArray['Database'];
                } elseif (isset($rowArray['database'])) {
                    $dbName = $rowArray['database'];
                } else {
                    // Get the first value if column name is unknown
                    $dbName = reset($rowArray);
                }
                
                if ($dbName && is_string($dbName)) {
                    $databases[] = $dbName;
                }
            }
            
            \Log::debug('SHOW DATABASES result', ['count' => count($databases)]);
            
            // If SHOW DATABASES returned results, use them
            if (!empty($databases)) {
                return $databases;
            }
            
            // Fallback: Try information_schema.SCHEMATA
            \Log::debug('Trying fallback: information_schema.SCHEMATA');
            $results = DB::connection($connectionName)
                ->select('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA');
            
            $databases = [];
            foreach ($results as $row) {
                $rowArray = (array)$row;
                if (isset($rowArray['SCHEMA_NAME'])) {
                    $databases[] = $rowArray['SCHEMA_NAME'];
                } elseif (isset($rowArray['schema_name'])) {
                    $databases[] = $rowArray['schema_name'];
                } else {
                    $dbName = reset($rowArray);
                    if ($dbName && is_string($dbName)) {
                        $databases[] = $dbName;
                    }
                }
            }
            
            \Log::debug('Fallback result', ['count' => count($databases)]);
            
            return $databases;
            
        } catch (\Exception $e) {
            \Log::error('MySQL database fetch failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function getPgsqlDatabases(string $connectionName): array
    {
        \Log::debug('Fetching PostgreSQL databases');
        
        try {
            $results = DB::connection($connectionName)
                ->select("SELECT datname FROM pg_database WHERE datistemplate = false");
            
            $databases = [];
            foreach ($results as $row) {
                $rowArray = (array)$row;
                if (isset($rowArray['datname'])) {
                    $databases[] = $rowArray['datname'];
                }
            }
            
            return $databases;
            
        } catch (\Exception $e) {
            \Log::error('PostgreSQL database fetch failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function getSqliteDatabases(DatabaseConnection $connection): array
    {
        // SQLite only has one database (the file itself)
        return ['main'];
    }

    public function getTables(DatabaseConnection $connection, string $database): array
    {
        \Log::info('Getting tables', [
            'connection_id' => $connection->id,
            'database' => $database,
            'driver' => $connection->driver
        ]);
        
        $tempConnection = 'temp_' . $connection->id . '_' . $database . '_' . time();
        
        try {
            $config = $this->buildConnectionConfig($connection);
            $config['database'] = $database;
            
            Config::set("database.connections.{$tempConnection}", $config);
            
            $tables = [];
            
            if ($connection->driver === 'mysql') {
                $results = DB::connection($tempConnection)->select('SHOW TABLES');
                
                foreach ($results as $row) {
                    $rowArray = (array)$row;
                    $tableName = $rowArray['Tables_in_' . $database] ?? reset($rowArray);
                    if ($tableName && is_string($tableName)) {
                        $tables[] = $tableName;
                    }
                }
            } elseif ($connection->driver === 'pgsql') {
                $results = DB::connection($tempConnection)
                    ->select("SELECT table_name FROM information_schema.tables 
                             WHERE table_schema = 'public' 
                             AND table_type = 'BASE TABLE'");
                
                foreach ($results as $row) {
                    $rowArray = (array)$row;
                    if (isset($rowArray['table_name'])) {
                        $tables[] = $rowArray['table_name'];
                    }
                }
            }
            
            Config::set("database.connections.{$tempConnection}", null);
            
            \Log::info('Tables fetched successfully', ['count' => count($tables)]);
            return $tables;
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch tables', [
                'connection_id' => $connection->id,
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to fetch tables: " . $e->getMessage());
        }
    }

    public function getTableColumns(DatabaseConnection $connection, string $database, string $table): array
    {
        \Log::info('Getting columns', [
            'connection_id' => $connection->id,
            'database' => $database,
            'table' => $table
        ]);
        
        $tempConnection = 'temp_' . $connection->id . '_' . $database . '_' . time();
        
        try {
            $config = $this->buildConnectionConfig($connection);
            $config['database'] = $database;
            
            Config::set("database.connections.{$tempConnection}", $config);
            
            $columns = [];
            
            if ($connection->driver === 'mysql') {
                $results = DB::connection($tempConnection)
                    ->select("SHOW COLUMNS FROM `{$table}`");
                
                foreach ($results as $row) {
                    $rowArray = (array)$row;
                    $columns[] = [
                        'name' => $rowArray['Field'] ?? null,
                        'type' => $rowArray['Type'] ?? null,
                        'nullable' => ($rowArray['Null'] ?? '') === 'YES',
                        'default' => $rowArray['Default'] ?? null,
                    ];
                }
            } elseif ($connection->driver === 'pgsql') {
                $results = DB::connection($tempConnection)
                    ->select("SELECT 
                            column_name as name,
                            data_type as type,
                            is_nullable as nullable,
                            column_default as default_value
                        FROM information_schema.columns 
                        WHERE table_catalog = ? 
                        AND table_name = ?
                        ORDER BY ordinal_position", [$database, $table]);
                
                foreach ($results as $row) {
                    $rowArray = (array)$row;
                    $columns[] = [
                        'name' => $rowArray['name'] ?? null,
                        'type' => $rowArray['type'] ?? null,
                        'nullable' => strtoupper($rowArray['nullable'] ?? 'NO') === 'YES',
                        'default' => $rowArray['default_value'] ?? null,
                    ];
                }
            }
            
            Config::set("database.connections.{$tempConnection}", null);
            
            \Log::info('Columns fetched successfully', ['count' => count($columns)]);
            
            // Extract just the column names for the frontend
            $columnNames = array_map(function($col) {
                return $col['name'];
            }, $columns);
            
            \Log::info('Returning column names', ['columns' => $columnNames]);
            
            return $columnNames;
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch columns', [
                'connection_id' => $connection->id,
                'database' => $database,
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to fetch columns: " . $e->getMessage());
        }
    }

    private function buildConnectionConfig(DatabaseConnection $connection): array
    {
        return [
            'driver' => $connection->driver,
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $connection->database,
            'username' => $connection->username,
            'password' => $connection->password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => false,
        ];
    }

    /**
     * Test if a database connection is valid
     */
    public function testConnection(DatabaseConnection $connection): bool
    {
        \Log::info('Testing connection', ['connection_id' => $connection->id]);
        
        $tempConnection = 'test_' . $connection->id . '_' . time();
        
        try {
            $config = $this->buildConnectionConfig($connection);
            Config::set("database.connections.{$tempConnection}", $config);
            
            DB::connection($tempConnection)->getPdo();
            
            Config::set("database.connections.{$tempConnection}", null);
            
            \Log::info('Connection test successful', ['connection_id' => $connection->id]);
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}