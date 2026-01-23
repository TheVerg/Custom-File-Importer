<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\GroupedImportController;
use App\Http\Controllers\DataCleaningController;

// Authentication Routes
Auth::routes();

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Protected routes
// Route::middleware(['auth', 'web'])->group(function () {
//     Route::get('/import', [ImportController::class, 'index'])->name('import.index');
//     Route::post('/import/upload', [ImportController::class, 'uploadFile'])->name('import.upload');
//     Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
//     Route::post('/import/start', [ImportController::class, 'startImport'])->name('import.start');
//     Route::get('/import/job/{importJob}/status', [ImportController::class, 'getJobStatus'])->name('import.job.status');
    
//     // AJAX endpoints
//     Route::get('/connection/{connection}/databases', [ImportController::class, 'getDatabases'])->name('connection.databases');
//     Route::post('/connection/{connection}/tables', [ImportController::class, 'getTables'])->name('connection.tables');
//     Route::post('/connection/{connection}/columns', [ImportController::class, 'getColumns'])->name('connection.columns');
// });

// Import routes
// Route::prefix('import')->group(function () {
//     Route::get('/', [ImportController::class, 'index'])->name('import.index');
//     Route::post('/', [ImportController::class, 'store'])->name('import.store');
//    // Route::get('/databases', [ImportController::class, 'getDatabases'])->name('import.databases');
// });

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::middleware(['auth', 'web'])->group(function () {
    // Main import page
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    
    // Step 1: Upload file
    Route::post('/import/upload', [ImportController::class, 'uploadFile'])->name('import.upload');
    
    // Step 2: Get databases
    Route::get('/import/connections/{connection}/databases', [ImportController::class, 'getDatabases'])->name('import.connection.databases');
    
    // Step 3: Get tables
    Route::match(['get', 'post'], '/import/connections/{connection}/tables', [ImportController::class, 'getTables'])->name('import.connection.tables');
    
    // Step 4: Get columns
    Route::match(['get', 'post'], '/import/connections/{connection}/columns', [ImportController::class, 'getColumns'])->name('import.connection.columns');
    
    // Step 5: Preview data
    Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    
    // Step 6: Start import
    Route::post('/import/start', [ImportController::class, 'startImport'])->name('import.start');
    
    // Get job status
    Route::get('/import/jobs/{job}/status', [ImportController::class, 'getJobStatus'])->name('import.jobs.status');
    
    // Grouped Import Routes
    Route::prefix('grouped-import')->name('grouped.import.')->group(function () {
        Route::get('/', [GroupedImportController::class, 'index'])->name('index');
        Route::post('/upload', [GroupedImportController::class, 'uploadFile'])->name('upload');
        Route::get('/columns', [GroupedImportController::class, 'getLoanTypeColumns'])->name('columns');
        Route::get('/sample', [GroupedImportController::class, 'getLoanTypeSample'])->name('sample');
        Route::post('/preview', [GroupedImportController::class, 'preview'])->name('preview');
        Route::post('/start', [GroupedImportController::class, 'startImport'])->name('start');
    });
});

Route::get('/debug/databases/{id}', function ($id) {
    try {
        $connection = \App\Models\DatabaseConnection::findOrFail($id);
        $databaseService = new \App\Services\Import\DatabaseService();
        
        // Test connection first
        if (!$databaseService->testConnection($connection)) {
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed'
            ]);
        }
        
        // Get databases
        $databases = $databaseService->getDatabases($connection);
        
        return response()->json([
            'success' => true,
            'connection' => [
                'id' => $connection->id,
                'name' => $connection->name,
                'driver' => $connection->driver,
                'host' => $connection->host,
                'port' => $connection->port,
                'username' => $connection->username,
                'password' => $connection->password ? '***' : null
            ],
            'databases' => $databases,
            'count' => count($databases)
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Debug route error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
        ], 500);
    }
});


// Data Cleaning Routes
Route::prefix('data-cleaning')->group(function () {
    Route::get('/', [DataCleaningController::class, 'index'])->name('data.cleaning.index');
    Route::post('/upload', [DataCleaningController::class, 'uploadFile'])->name('data.cleaning.upload');
    Route::get('/rules', [DataCleaningController::class, 'getAvailableRules'])->name('data.cleaning.rules');
    Route::post('/preview', [DataCleaningController::class, 'previewCleaning'])->name('data.cleaning.preview');
    Route::post('/apply', [DataCleaningController::class, 'applyCleaning'])->name('data.cleaning.apply');
    Route::get('/download/{fileName}', [DataCleaningController::class, 'downloadCleanedFile'])->name('data.cleaning.download');
    Route::post('/save-template', [DataCleaningController::class, 'saveTemplate'])->name('data.cleaning.save.template');
    Route::get('/templates', [DataCleaningController::class, 'getTemplates'])->name('data.cleaning.templates');
    Route::get('/template/{templateName}', [DataCleaningController::class, 'loadTemplate'])->name('data.cleaning.load.template');
});