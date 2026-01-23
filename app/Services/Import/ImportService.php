<?php

namespace App\Services\Import;

use App\Models\ImportJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class ImportService
{
    private DatabaseService $databaseService;
    private const BATCH_SIZE = 1000;

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function processImport(ImportJob $importJob): void
    {
        Log::info('Starting import process', ['job_id' => $importJob->id]);
        
        $importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $connectionName = 'import_' . $importJob->id;
        $batch = [];
        $successful = 0;
        $failed = 0;
        $rowIndex = 0;
        $totalRows = 0;

        try {
            // Setup database connection
            $config = $importJob->connection->getConnectionString();
            $config['database'] = $importJob->database_name;
            Config::set("database.connections.{$connectionName}", $config);

            // Get file path
            $filePath = Storage::path($importJob->file_path);
            
            Log::info('Starting file processing', [
                'job_id' => $importJob->id,
                'file_type' => $importJob->file_type
            ]);

            // Start transaction
            DB::connection($connectionName)->beginTransaction();

            Log::debug('Starting row processing', [
                'job_id' => $importJob->id,
                'mappings' => $importJob->column_mappings
            ]);

            // Process file in chunks to minimize memory usage
            foreach ($this->readFileInChunks($filePath, $importJob->file_type, 500) as $chunk) {
                foreach ($chunk as $row) {
                    $rowIndex++;
                    $totalRows++;
                    
                    try {
                        Log::debug('Processing row', [
                            'job_id' => $importJob->id,
                            'row_index' => $rowIndex,
                            'raw_row' => $row,
                            'mappings' => $importJob->column_mappings
                        ]);

                        $mappedData = $this->mapRowData($row, $importJob->column_mappings);
                        
                        Log::debug('Mapped row data', [
                            'job_id' => $importJob->id,
                            'row_index' => $rowIndex,
                            'mapped_data' => $mappedData
                        ]);
                        
                        $batch[] = $mappedData;

                        // Process batch when size is reached
                        if (count($batch) >= self::BATCH_SIZE) {
                            $this->processBatch($connectionName, $importJob->table_name, $batch, $successful, $failed);
                            $batch = [];
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error("Row processing failed", [
                            'job_id' => $importJob->id,
                            'row_index' => $rowIndex,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }

                    // Update progress periodically
                    if ($rowIndex % 10 === 0) {
                        $this->updateProgress($importJob, $rowIndex, $successful, $failed);
                    }
                }
                
                // Garbage collection after each chunk
                gc_collect_cycles();
            }

            $importJob->update(['total_rows' => $totalRows]);

            // Process any remaining items in the batch
            if (!empty($batch)) {
                $this->processBatch($connectionName, $importJob->table_name, $batch, $successful, $failed);
            }

            // Commit transaction if no errors
            DB::connection($connectionName)->commit();

            $status = $this->determineStatus($successful, $failed, count($data));
            
            Log::info('Import completed', [
                'job_id' => $importJob->id,
                'status' => $status,
                'successful' => $successful,
                'failed' => $failed
            ]);

        } catch (\Exception $e) {
            // Rollback transaction on error
            if (isset($connectionName) && DB::connection($connectionName)->transactionLevel() > 0) {
                DB::connection($connectionName)->rollBack();
            }

            Log::error('Import process failed', [
                'job_id' => $importJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $status = 'failed';
            $errorMessage = $e->getMessage();
        }

        // Final update
        $updateData = [
            'status' => $status,
            'processed_rows' => count($data),
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'completed_at' => now(),
        ];

        if (isset($errorMessage)) {
            $updateData['error_message'] = $errorMessage;
        }

        $importJob->update($updateData);
    }

    /**
     * Get file headers from CSV or Excel file
     */
    public function getFileHeaders(string $filePath, string $fileType): array
    {
        if ($fileType === 'csv') {
            $reader = ReaderEntityFactory::createCSVReader();
            $reader->setFieldDelimiter(',');
            $reader->setFieldEnclosure('"');
        } else {
            $reader = ReaderEntityFactory::createXLSXReader();
        }
        
        $reader->open($filePath);
        $headers = [];
        
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    $cells = $row->getCells();
                    $headers = array_map('trim', array_map(function($cell) {
                        return $cell->getValue();
                    }, $cells));
                    break 2; // Break out of both loops
                }
            }
        }
        
        $reader->close();
        
        return $headers;
    }

    /**
     * Get sample data rows from file
     */
    public function getFileSample(string $filePath, string $fileType, int $sampleSize = 5): array
    {
        if ($fileType === 'csv') {
            $reader = ReaderEntityFactory::createCSVReader();
            $reader->setFieldDelimiter(',');
            $reader->setFieldEnclosure('"');
        } else {
            $reader = ReaderEntityFactory::createXLSXReader();
        }
        
        $reader->open($filePath);
        $data = [];
        $rowCount = 0;
        
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $cells = $row->getCells();
                
                // Skip header row
                if ($rowIndex === 1) {
                    continue;
                }
                
                // Collect sample rows as arrays (not associative arrays)
                if ($rowCount < $sampleSize) {
                    $rowData = array_map(function($cell) {
                        return $cell->getValue();
                    }, $cells);
                    $data[] = $rowData;
                    $rowCount++;
                } else {
                    break 2; // Exit both loops
                }
            }
        }
        
        $reader->close();
        
        return $data;
    }

    private function processBatch(string $connectionName, string $table, array &$batch, int &$successful, int &$failed): void
    {
        if (empty($batch)) {
            return;
        }

        try {
            // Log the table structure for debugging
            try {
                $columns = DB::connection($connectionName)
                    ->getSchemaBuilder()
                    ->getColumnListing($table);
                    
                Log::debug('Table columns', [
                    'table' => $table,
                    'columns' => $columns
                ]);
            } catch (\Exception $e) {
                Log::warning('Could not get table columns', [
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
            }

            Log::debug('Inserting batch', [
                'table' => $table,
                'batch_size' => count($batch),
                'first_item' => $batch[0] ?? null
            ]);
            
            // Log the actual SQL that will be executed (first row only to avoid log spam)
            if (!empty($batch[0])) {
                $firstRow = $batch[0];
                $columns = array_map(function($col) {
                    return '`' . str_replace('`', '``', $col) . '`';
                }, array_keys($firstRow));
                
                $placeholders = implode(', ', array_fill(0, count($firstRow), '?'));
                $values = array_values($firstRow);
                
                Log::debug('Sample SQL (first row only)', [
                    'sql' => "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES ($placeholders)",
                    'bindings' => $values
                ]);
            }
            
            $result = DB::connection($connectionName)
                ->table($table)
                ->insert($batch);
            
            Log::debug('Batch insert result', [
                'table' => $table,
                'success' => $result,
                'rows_affected' => $result ? count($batch) : 0
            ]);
            
            $successful += count($batch);
            $batch = [];
        } catch (\Exception $e) {
            Log::error('Batch insert failed', [
                'table' => $table,
                'error' => $e->getMessage(),
                'first_row' => $batch[0] ?? null,
                'batch_size' => count($batch)
            ]);
            
            $failed += count($batch);
        }

}

/**
 * Read file in chunks to minimize memory usage
 * Uses a generator to yield chunks of data
 */
private function readFileInChunks(string $filePath, string $fileType, int $chunkSize = 500): \Generator
{
    if ($fileType === 'csv') {
        $reader = ReaderEntityFactory::createCSVReader();
        $reader->setFieldDelimiter(',');
        $reader->setFieldEnclosure('"');
    } else {
        $reader = ReaderEntityFactory::createXLSXReader();
    }
    
    $reader->open($filePath);
    
    $headers = [];
    $chunk = [];
    $rowsInChunk = 0;
    
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $cells = $row->getCells();
            
            // First row is headers
            if ($rowIndex === 1) {
                $headers = array_map('trim', array_map(function($cell) {
                    return $cell->getValue();
                }, $cells));
                continue;
            }
            
            // Convert row to associative array using headers
            $rowData = [];
            foreach ($cells as $index => $cell) {
                $header = $headers[$index] ?? $index;
                $rowData[$header] = $cell->getValue();
            }
            
            $chunk[] = $rowData;
            $rowsInChunk++;
            
            // Yield chunk when size is reached
            if ($rowsInChunk >= $chunkSize) {
                yield $chunk;
                $chunk = [];
                $rowsInChunk = 0;
            }
        }
        break; // Only process first sheet
    }
    
    // Yield remaining rows
    if (!empty($chunk)) {
        yield $chunk;
    }
    
    $reader->close();
}

/**
 * Legacy method - kept for backwards compatibility
 * Reads entire file into memory
 */
private function readFile(string $filePath, string $fileType): array
{
    $data = [];
    
    foreach ($this->readFileInChunks($filePath, $fileType, 5000) as $chunk) {
        $data = array_merge($data, $chunk);
    }
    
    return $data;
}

private function mapRowData(array $row, array $mappings): array
    {
        $mappedData = [];
        
        foreach ($mappings as $source => $target) {
            // Trim any whitespace from source and target column names
            $source = trim($source);
            $target = trim($target);
            
            // Skip if target column is empty
            if (empty($target)) {
                continue;
            }
            
            // Skip if source column doesn't exist in the row
            if (!array_key_exists($source, $row)) {
                continue;
            }
            
            // Handle date format conversion for known date fields
            if (in_array($target, ['create_date', 'due_date', 'value_date', 'maturity_date'])) {
                $mappedData[$target] = $this->formatDate($row[$source] ?? null);
            } 
            // Handle numeric fields
            elseif (in_array($target, ['principal_balance', 'disbursed', 'carrying_amount', 'interest_rate', 'tenor'])) {
                $mappedData[$target] = $this->formatNumber($row[$source] ?? 0);
            }
            // Default case - direct mapping
            else {
                $mappedData[$target] = $row[$source] ?? null;
            }
        }
        
        // Add timestamps
        $mappedData['created_at'] = now();
        $mappedData['updated_at'] = now();
        
        return $mappedData;
    }

    private function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            // Try parsing with day/month/year format first
            $parsedDate = \Carbon\Carbon::createFromFormat('d/m/Y', $date);
            if ($parsedDate) {
                return $parsedDate->format('Y-m-d');
            }
            
            // If that fails, try parsing as is (for already formatted dates)
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function formatNumber($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_string($value)) {
            // Remove any non-numeric characters except decimal point and minus sign
            $value = preg_replace('/[^0-9.-]/', '', $value);
            
            // Handle empty strings after cleanup
            if ($value === '' || $value === '-') {
                return 0;
            }
        }
        
        return (float)$value;
    }

    private function updateProgress(ImportJob $importJob, int $processed, int $successful, int $failed): void
    {
        $importJob->update([
            'processed_rows' => $processed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'status' => $this->determineStatus($successful, $failed, $importJob->total_rows)
        ]);
    }

    private function determineStatus(int $successful, int $failed, int $total): string
    {
        if ($failed === 0 && $successful === $total) {
            return 'completed';
        }
        
        if ($successful > 0 && $failed > 0) {
            return 'partial';
        }
        
        if ($successful > 0) {
            return 'completed';
        }
        
        return 'failed';
    }
}