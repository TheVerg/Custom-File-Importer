<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\ImportJob;
use App\Services\Import\DatabaseService;
use App\Services\Import\GroupedImportService;
use App\Services\Import\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GroupedImportController extends Controller
{
    private DatabaseService $databaseService;
    private GroupedImportService $groupedImportService;
    private ImportService $importService;

    public function __construct(
        DatabaseService $databaseService,
        GroupedImportService $groupedImportService,
        ImportService $importService
    ) {
        $this->databaseService = $databaseService;
        $this->groupedImportService = $groupedImportService;
        $this->importService = $importService;
        $this->middleware('auth');
    }

    /**
     * Show the grouped import page
     */
    public function index()
    {
        \Log::info('GroupedImportController@index accessed');
        $connections = DatabaseConnection::where('is_active', true)->get();
        
        return view('import.grouped.index', compact('connections'));
    }

    /**
     * Upload and analyze a grouped file
     */
    public function uploadFile(Request $request)
    {
        // Increase execution time limit for large file uploads
        set_time_limit(300); // 5 minutes
        
        \Log::info('Uploading grouped file', ['file_name' => $request->file('file')?->getClientOriginalName()]);
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);
        
        if ($validator->fails()) {
            \Log::error('Grouped file upload validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            $file = $request->file('file');
            $fileName = time() . '_grouped_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('imports', $fileName);
            $fileType = $file->getClientOriginalExtension();
            
            \Log::info('Grouped file stored successfully', [
                'file_path' => $filePath,
                'file_type' => $fileType
            ]);
            
            // Parse the grouped file
            $groups = $this->groupedImportService->parseGroupedFile(
                Storage::path($filePath),
                $fileType
            );
            
            if (empty($groups)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No loan type groups found in the file. Please ensure the file contains proper grouping headers like "Loan Type: 1050101-MAJIC Agricultural Loans"'
                ], 422);
            }
            
            // Get loan types summary
            $loanTypes = $this->groupedImportService->getLoanTypes(
                Storage::path($filePath),
                $fileType
            );
            
            \Log::info('Grouped file parsed successfully', [
                'groups_count' => count($groups),
                'loan_types' => $loanTypes
            ]);
            
            return response()->json([
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $fileType,
                'groups' => $groups,
                'loan_types' => $loanTypes,
                'total_rows' => array_sum(array_map(fn($g) => count($g['data']), $groups))
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Grouped file upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get columns available for a specific loan type
     */
    public function getLoanTypeColumns(Request $request)
    {
        \Log::info('Getting loan type columns', [
            'file_path' => $request->file_path,
            'file_type' => $request->file_type,
            'loan_type_code' => $request->loan_type_code
        ]);
        
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'loan_type_code' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            $columns = $this->groupedImportService->getLoanTypeColumns(
                Storage::path($request->file_path),
                $request->file_type,
                $request->loan_type_code
            );
            
            return response()->json([
                'success' => true,
                'columns' => $columns
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get loan type columns', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sample data for a specific loan type
     */
    public function getLoanTypeSample(Request $request)
    {
        \Log::info('Getting loan type sample', [
            'file_path' => $request->file_path,
            'file_type' => $request->file_type,
            'loan_type_code' => $request->loan_type_code
        ]);
        
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'loan_type_code' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            $sample = $this->groupedImportService->getLoanTypeSample(
                Storage::path($request->file_path),
                $request->file_type,
                $request->loan_type_code,
                5
            );
            
            return response()->json([
                'success' => true,
                'sample' => $sample
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get loan type sample', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import mapping for a specific loan type
     */
    public function preview(Request $request)
    {
        \Log::info('Grouped import preview requested', [
            'connection_id' => $request->connection_id,
            'database' => $request->database,
            'table' => $request->table,
            'loan_type_code' => $request->loan_type_code
        ]);
        
        $validator = Validator::make($request->all(), [
            'connection_id' => 'required|exists:database_connections,id',
            'database' => 'required|string',
            'table' => 'required|string',
            'file_path' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'loan_type_code' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            $connection = DatabaseConnection::findOrFail($request->connection_id);
            
            // Get CSV columns for this loan type
            $csvColumns = $this->groupedImportService->getLoanTypeColumns(
                Storage::path($request->file_path),
                $request->file_type,
                $request->loan_type_code
            );
            
            // Get database columns
            $dbColumns = $this->databaseService->getTableColumns(
                $connection,
                $request->database,
                $request->table
            );
            
            // Get sample data for this loan type
            $sample = $this->groupedImportService->getLoanTypeSample(
                Storage::path($request->file_path),
                $request->file_type,
                $request->loan_type_code,
                5
            );
            
            \Log::info('Grouped import preview generated successfully', [
                'csv_columns_count' => count($csvColumns),
                'db_columns_count' => count($dbColumns),
                'sample_rows' => count($sample)
            ]);
            
            return response()->json([
                'success' => true,
                'csv_columns' => $csvColumns,
                'db_columns' => $dbColumns,
                'sample' => $sample,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Grouped import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start grouped import process
     */
    public function startImport(Request $request)
    {
        \Log::info('Starting grouped import', [
            'connection_id' => $request->connection_id,
            'database' => $request->database,
            'table' => $request->table,
            'loan_types' => $request->loan_types
        ]);
        
        $validator = Validator::make($request->all(), [
            'connection_id' => 'required|exists:database_connections,id',
            'database' => 'required|string',
            'table' => 'required|string',
            'file_path' => 'required|string',
            'file_name' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'loan_types' => 'required|array',
            'loan_types.*' => 'required|string',
            'mappings' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            \Log::error('Grouped import validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            // Create import job with grouped information
            $importJob = ImportJob::create([
                'user_id' => Auth::id(),
                'connection_id' => $request->connection_id,
                'database_name' => $request->database,
                'table_name' => $request->table,
                'file_name' => $request->file_name,
                'file_path' => $request->file_path,
                'file_type' => $request->file_type,
                'column_mappings' => $request->mappings,
                'import_settings' => [
                    'is_grouped_import' => true,
                    'loan_types' => $request->loan_types,
                    'single_mapping' => true
                ],
                'status' => 'pending',
            ]);
            
            \Log::info('Grouped import job created', ['job_id' => $importJob->id]);
            
            // Process the grouped import
            $this->processGroupedImport($importJob);
            
            return response()->json([
                'success' => true,
                'message' => 'Grouped import started successfully',
                'job_id' => $importJob->id,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Start grouped import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process grouped import by loan type
     */
    private function processGroupedImport(ImportJob $importJob): void
    {
        \Log::info('Processing grouped import', ['job_id' => $importJob->id]);
        
        $importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $settings = $importJob->import_settings;
            $loanTypes = $settings['loan_types'] ?? [];
            $mappings = $importJob->column_mappings;
            $totalRows = 0;
            $successfulRows = 0;
            $failedRows = 0;

            // Process each loan type
            foreach ($loanTypes as $loanType) {
                \Log::info('Processing loan type', [
                    'job_id' => $importJob->id,
                    'loan_type_code' => $loanType
                ]);

                // Get data for this loan type
                $groups = $this->groupedImportService->parseGroupedFile(
                    Storage::path($importJob->file_path),
                    $importJob->file_type
                );

                foreach ($groups as $group) {
                    if ($group['loan_type_code'] === $loanType) {
                        $totalRows += count($group['data']);
                        
                        // Create a temporary import job for this loan type
                        $tempJob = clone $importJob;
                        $tempJob->column_mappings = $mappings; // Use single mapping for all loan types
                        
                        // Process this group's data
                        $this->processLoanTypeData($tempJob, $group['data'], $successfulRows, $failedRows);
                        break;
                    }
                }
            }

            $importJob->update([
                'total_rows' => $totalRows,
                'processed_rows' => $totalRows,
                'successful_rows' => $successfulRows,
                'failed_rows' => $failedRows,
                'status' => $failedRows === 0 ? 'completed' : 'partial',
                'completed_at' => now(),
            ]);

            \Log::info('Grouped import completed', [
                'job_id' => $importJob->id,
                'total_rows' => $totalRows,
                'successful' => $successfulRows,
                'failed' => $failedRows
            ]);

        } catch (\Exception $e) {
            \Log::error('Grouped import process failed', [
                'job_id' => $importJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $importJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Process data for a specific loan type
     */
    private function processLoanTypeData(ImportJob $importJob, array $data, int &$successful, int &$failed): void
    {
        $connectionName = 'import_' . $importJob->id;
        $batch = [];
        
        try {
            // Setup database connection
            $config = $importJob->connection->getConnectionString();
            $config['database'] = $importJob->database_name;
            \Illuminate\Support\Facades\Config::set("database.connections.{$connectionName}", $config);
            
            foreach ($data as $row) {
                try {
                    $mappedData = $this->mapRowData($row, $importJob->column_mappings);
                    $batch[] = $mappedData;
                    
                    // Process batch when size is reached
                    if (count($batch) >= 100) {
                        $this->insertBatch($connectionName, $importJob->table_name, $batch);
                        $successful += count($batch);
                        $batch = [];
                    }
                } catch (\Exception $e) {
                    $failed++;
                    \Log::error('Row processing failed', [
                        'job_id' => $importJob->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Process remaining items in batch
            if (!empty($batch)) {
                $this->insertBatch($connectionName, $importJob->table_name, $batch);
                $successful += count($batch);
            }
            
        } catch (\Exception $e) {
            \Log::error('Loan type processing failed', [
                'job_id' => $importJob->id,
                'error' => $e->getMessage()
            ]);
            $failed += count($data);
        }
    }
    
    /**
     * Insert batch of data into database
     */
    private function insertBatch(string $connectionName, string $table, array $batch): void
    {
        if (empty($batch)) {
            return;
        }
        
        \Illuminate\Support\Facades\DB::connection($connectionName)
            ->table($table)
            ->insert($batch);
    }

    /**
     * Map row data using column mappings (simplified version)
     */
    private function mapRowData(array $row, array $mappings): array
    {
        $mappedData = [];
        
        foreach ($mappings as $source => $target) {
            if (empty($target) || !array_key_exists($source, $row)) {
                continue;
            }
            
            $mappedData[$target] = $row[$source] ?? null;
        }
        
        // Add timestamps
        $mappedData['created_at'] = now();
        $mappedData['updated_at'] = now();
        
        return $mappedData;
    }
}
