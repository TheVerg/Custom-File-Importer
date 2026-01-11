<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\ImportJob;
use App\Services\Import\DatabaseService;
use App\Services\Import\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    private DatabaseService $databaseService;
    private ImportService $importService;

    public function __construct(DatabaseService $databaseService, ImportService $importService)
    {
        $this->databaseService = $databaseService;
        $this->importService = $importService;
        $this->middleware('auth');
    }

    public function index()
    {
        \Log::info('ImportController@index accessed');
        $connections = DatabaseConnection::where('is_active', true)->get();
        $importJobs = ImportJob::with('connection')->latest()->paginate(10);
        
        return view('import.index', compact('connections', 'importJobs'));
    }

    public function getDatabases(DatabaseConnection $connection)
    {
        \Log::info('Getting databases for connection', [
            'connection_id' => $connection->id,
            'driver' => $connection->driver,
            'host' => $connection->host
        ]);
        
        try {
            $databases = $this->databaseService->getDatabases($connection);
            
            if (empty($databases)) {
                \Log::warning('No databases found for connection', ['connection_id' => $connection->id]);
                return response()->json([
                    'success' => false,
                    'error' => 'No databases found for this connection',
                    'debug' => [
                        'connection_id' => $connection->id,
                        'driver' => $connection->driver,
                        'host' => $connection->host
                    ]
                ], 200);
            }
            
            \Log::info('Databases fetched successfully', [
                'connection_id' => $connection->id,
                'count' => count($databases)
            ]);
            
            return response()->json([
                'success' => true,
                'databases' => $databases
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch databases', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'connection' => [
                    'driver' => $connection->driver,
                    'host' => $connection->host,
                    'port' => $connection->port,
                    'username' => $connection->username
                ]
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTables(Request $request, DatabaseConnection $connection)
    {
        \Log::info('Getting tables', [
            'connection_id' => $connection->id,
            'database' => $request->database
        ]);
        
        $request->validate([
            'database' => 'required|string'
        ]);
        
        try {
            $tables = $this->databaseService->getTables($connection, $request->database);
            \Log::info('Tables fetched successfully', ['count' => count($tables)]);
            
            return response()->json([
                'success' => true,
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch tables', [
                'connection_id' => $connection->id,
                'database' => $request->database,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getColumns(Request $request, DatabaseConnection $connection)
    {
        \Log::info('Getting columns', [
            'connection_id' => $connection->id,
            'database' => $request->database,
            'table' => $request->table
        ]);
        
        $request->validate([
            'database' => 'required|string',
            'table' => 'required|string'
        ]);
        
        try {
            $columns = $this->databaseService->getTableColumns(
                $connection,
                $request->database,
                $request->table
            );
            
            \Log::info('Columns fetched successfully', ['count' => count($columns)]);
            
            return response()->json([
                'success' => true,
                'columns' => $columns
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch columns', [
                'connection_id' => $connection->id,
                'database' => $request->database,
                'table' => $request->table,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadFile(Request $request)
    {
        \Log::info('Uploading file', ['file_name' => $request->file('file')?->getClientOriginalName()]);
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);
        
        if ($validator->fails()) {
            \Log::error('File upload validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('imports', $fileName);
            $fileType = $file->getClientOriginalExtension();
            
            \Log::info('File stored successfully', [
                'file_path' => $filePath,
                'file_type' => $fileType
            ]);
            
            // Get file headers
            $headers = $this->importService->getFileHeaders(
                Storage::path($filePath),
                $fileType
            );
            
            // Get sample data
            $sample = $this->importService->getFileSample(
                Storage::path($filePath),
                $fileType,
                5
            );
            
            \Log::info('File parsed successfully', [
                'headers_count' => count($headers),
                'sample_rows' => count($sample)
            ]);
            
            return response()->json([
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $fileType,
                'headers' => $headers,
                'sample' => $sample
            ]);
            
        } catch (\Exception $e) {
            \Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function preview(Request $request)
    {
        \Log::info('Preview requested', [
            'connection_id' => $request->connection_id,
            'database' => $request->database,
            'table' => $request->table,
            'file_path' => $request->file_path
        ]);
        
        $validator = Validator::make($request->all(), [
            'connection_id' => 'required|exists:database_connections,id',
            'database' => 'required|string',
            'table' => 'required|string',
            'file_path' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
        ]);
        
        if ($validator->fails()) {
            \Log::error('Preview validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            $connection = DatabaseConnection::findOrFail($request->connection_id);
            
            // Get CSV headers
            $csvHeaders = $this->importService->getFileHeaders(
                Storage::path($request->file_path),
                $request->file_type
            );
            
            // Get database columns
            $dbColumns = $this->databaseService->getTableColumns(
                $connection,
                $request->database,
                $request->table
            );
            
            // Get file sample
            $sample = $this->importService->getFileSample(
                Storage::path($request->file_path),
                $request->file_type,
                5
            );
            
            \Log::info('Preview generated successfully', [
                'csv_headers_count' => count($csvHeaders),
                'db_columns_count' => count($dbColumns),
                'sample_rows' => count($sample)
            ]);
            
            return response()->json([
                'success' => true,
                'csv_headers' => $csvHeaders,
                'db_columns' => $dbColumns,
                'sample' => $sample,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function startImport(Request $request)
    {
        \Log::info('Starting import', [
            'connection_id' => $request->connection_id,
            'database' => $request->database,
            'table' => $request->table
        ]);
        
        $validator = Validator::make($request->all(), [
            'connection_id' => 'required|exists:database_connections,id',
            'database' => 'required|string',
            'table' => 'required|string',
            'file_path' => 'required|string',
            'file_name' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'mappings' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            \Log::error('Start import validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }
        
        try {
            // Create import job
            $importJob = ImportJob::create([
                'user_id' => Auth::id(),
                'connection_id' => $request->connection_id,
                'database_name' => $request->database,
                'table_name' => $request->table,
                'file_name' => $request->file_name,
                'file_path' => $request->file_path,
                'file_type' => $request->file_type,
                'column_mappings' => $request->mappings,
                'status' => 'pending',
            ]);
            
            \Log::info('Import job created', ['job_id' => $importJob->id]);
            
            // For now, process immediately (you can queue this later)
            $this->importService->processImport($importJob);
            
            return response()->json([
                'success' => true,
                'message' => 'Import started successfully',
                'job_id' => $importJob->id,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Start import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getJobStatus(ImportJob $job)
    {
        \Log::info('Getting job status', ['job_id' => $job->id]);
        
        return response()->json([
            'success' => true,
            'status' => $job->status,
            'processed_rows' => $job->processed_rows,
            'total_rows' => $job->total_rows,
            'successful_rows' => $job->successful_rows,
            'failed_rows' => $job->failed_rows,
            'error_message' => $job->error_message,
        ]);
    }
}