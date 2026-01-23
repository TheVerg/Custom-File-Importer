<?php

namespace App\Http\Controllers;

use App\Services\Import\DataCleaningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class DataCleaningController extends Controller
{
    private DataCleaningService $dataCleaningService;

    public function __construct(DataCleaningService $dataCleaningService)
    {
        $this->dataCleaningService = $dataCleaningService;
        $this->middleware('auth');
    }

    /**
     * Show the data cleaning interface
     */
    public function index()
    {
        $rules = $this->dataCleaningService->getAvailableRules();
        return view('data-cleaning.index', compact('rules'));
    }

    /**
     * Upload file for cleaning
     */
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('cleaning', $fileName);
            $fileType = $file->getClientOriginalExtension();
            
            // Read file headers and sample data
            $reader = $this->getReader($fileType);
            $reader->open(Storage::path($filePath));
            
            $headers = [];
            $sampleData = [];
            $totalRows = 0;
            
            foreach ($reader->getSheetIterator() as $sheet) {
                $rowCount = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    foreach ($row->getCells() as $cell) {
                        $rowData[] = $cell->getValue();
                    }
                    
                    if ($rowCount === 0) {
                        $headers = $rowData;
                    } else {
                        $sampleData[] = $rowData;
                    }
                    
                    if (++$rowCount > 6) break; // Get first 5 data rows + header
                }
                break;
            }
            
            $reader->close();
            
            // Count total rows
            $reader = $this->getReader($fileType);
            $reader->open(Storage::path($filePath));
            
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $totalRows++;
                }
                break;
            }
            
            $reader->close();
            
            return response()->json([
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $fileType,
                'headers' => $headers,
                'sample_data' => $sampleData,
                'total_rows' => $totalRows - 1, // Exclude header
            ]);
            
        } catch (\Exception $e) {
            \Log::error('File upload failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview cleaning results
     */
    public function previewCleaning(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'cleaning_rules' => 'required|array',
            'transformations' => 'array',
            'options' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Read sample data (first 50 rows for preview)
            $sampleData = $this->readSampleData(
                Storage::path($request->file_path),
                $request->file_type,
                50
            );
            
            $validationErrors = [];
            $cleanedData = $this->dataCleaningService->cleanData(
                $sampleData, 
                $request->cleaning_rules, 
                $validationErrors
            );
            
            // Apply transformations if any
            if (!empty($request->transformations)) {
                $cleanedData = $this->dataCleaningService->applyColumnTransformations(
                    $cleanedData, 
                    $request->transformations
                );
            }
            
            // Apply options
            if ($request->options['remove_duplicates'] ?? false) {
                $uniqueColumns = $request->options['unique_columns'] ?? [];
                $cleanedData = $this->dataCleaningService->removeDuplicates($cleanedData, $uniqueColumns);
            }
            
            return response()->json([
                'success' => true,
                'original_sample' => $sampleData,
                'cleaned_sample' => $cleanedData,
                'validation_errors' => $validationErrors,
                'sample_size' => count($sampleData),
                'cleaned_size' => count($cleanedData),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Cleaning preview failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply cleaning and download cleaned file
     */
    public function applyCleaning(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'file_name' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'cleaning_rules' => 'required|array',
            'transformations' => 'array',
            'options' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Read all data
            $originalData = $this->readAllData(
                Storage::path($request->file_path),
                $request->file_type
            );
            
            $validationErrors = [];
            
            // Apply cleaning
            $cleanedData = $this->dataCleaningService->cleanData(
                $originalData, 
                $request->cleaning_rules, 
                $validationErrors
            );
            
            // Apply transformations
            if (!empty($request->transformations)) {
                $cleanedData = $this->dataCleaningService->applyColumnTransformations(
                    $cleanedData, 
                    $request->transformations
                );
            }
            
            // Apply options
            if ($request->options['remove_duplicates'] ?? false) {
                $uniqueColumns = $request->options['unique_columns'] ?? [];
                $cleanedData = $this->dataCleaningService->removeDuplicates($cleanedData, $uniqueColumns);
            }
            
            // Generate cleaning report
            $cleaningReport = $this->dataCleaningService->generateReport(
                $originalData,
                $cleanedData,
                $validationErrors
            );
            
            // Save cleaned file
            $cleanedFileName = 'cleaned_' . $request->file_name;
            $cleanedFilePath = 'cleaned/' . $cleanedFileName;
            
            $this->writeCleanedFile(
                $cleanedData,
                $cleanedFilePath,
                $request->file_type,
                $request->headers ?? []
            );
            
            return response()->json([
                'success' => true,
                'cleaned_file_path' => $cleanedFilePath,
                'cleaned_file_name' => $cleanedFileName,
                'cleaning_report' => $cleaningReport,
                'total_rows' => count($originalData),
                'cleaned_rows' => count($cleanedData),
                'validation_errors' => count($validationErrors),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Apply cleaning failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download cleaned file
     */
    public function downloadCleanedFile($fileName)
    {
        $filePath = 'cleaned/' . $fileName;
        
        if (!Storage::exists($filePath)) {
            abort(404, 'File not found');
        }
        
        return Storage::download($filePath, $fileName);
    }

    /**
     * Save cleaning template (rules + transformations)
     */
    public function saveTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cleaning_rules' => 'required|array',
            'transformations' => 'array',
            'options' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            // In a real app, you'd save to a database table
            // For now, save to storage
            $template = [
                'name' => $request->name,
                'description' => $request->description,
                'cleaning_rules' => $request->cleaning_rules,
                'transformations' => $request->transformations ?? [],
                'options' => $request->options ?? [],
                'created_at' => now()->toDateTimeString(),
                'created_by' => auth()->id(),
            ];
            
            $templateName = 'template_' . str_slug($request->name) . '_' . time() . '.json';
            Storage::put('cleaning_templates/' . $templateName, json_encode($template));
            
            return response()->json([
                'success' => true,
                'message' => 'Template saved successfully',
                'template_name' => $templateName,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Save template failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load saved templates
     */
    public function getTemplates()
    {
        try {
            $templates = [];
            
            if (Storage::exists('cleaning_templates')) {
                $files = Storage::files('cleaning_templates');
                
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                        $content = json_decode(Storage::get($file), true);
                        if ($content) {
                            $templates[] = [
                                'name' => $content['name'] ?? 'Untitled',
                                'description' => $content['description'] ?? '',
                                'file_name' => basename($file),
                                'created_at' => $content['created_at'] ?? '',
                            ];
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'templates' => $templates,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Get templates failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load a specific template
     */
    public function loadTemplate($templateName)
    {
        try {
            $filePath = 'cleaning_templates/' . $templateName;
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Template not found'
                ], 404);
            }
            
            $content = json_decode(Storage::get($filePath), true);
            
            return response()->json([
                'success' => true,
                'template' => $content,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Load template failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    private function getReader(string $fileType)
    {
        if ($fileType === 'csv') {
            $reader = ReaderEntityFactory::createCSVReader();
            $reader->setFieldDelimiter(',');
            $reader->setFieldEnclosure('"');
        } else {
            $reader = ReaderEntityFactory::createXLSXReader();
        }
        
        return $reader;
    }

    private function readSampleData(string $filePath, string $fileType, int $maxRows = 50): array
    {
        $data = [];
        $reader = $this->getReader($fileType);
        $reader->open($filePath);
        
        $rowCount = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex === 1) continue; // Skip header
                
                $rowData = [];
                foreach ($row->getCells() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $data[] = $rowData;
                
                if (++$rowCount >= $maxRows) break;
            }
            break;
        }
        
        $reader->close();
        return $data;
    }

    private function readAllData(string $filePath, string $fileType): array
    {
        $data = [];
        $reader = $this->getReader($fileType);
        $reader->open($filePath);
        
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex === 1) continue; // Skip header
                
                $rowData = [];
                foreach ($row->getCells() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $data[] = $rowData;
            }
            break;
        }
        
        $reader->close();
        return $data;
    }

    private function writeCleanedFile(array $data, string $filePath, string $fileType, array $headers = [])
    {
        if ($fileType === 'csv') {
            $writer = WriterEntityFactory::createCSVWriter();
            $writer->setFieldDelimiter(',');
            $writer->setFieldEnclosure('"');
        } else {
            $writer = WriterEntityFactory::createXLSXWriter();
        }
        
        $writer->openToFile(Storage::path($filePath));
        
        // Write headers if provided
        if (!empty($headers)) {
            $headerRow = WriterEntityFactory::createRowFromArray($headers);
            $writer->addRow($headerRow);
        }
        
        // Write data
        foreach ($data as $rowData) {
            $row = WriterEntityFactory::createRowFromArray($rowData);
            $writer->addRow($row);
        }
        
        $writer->close();
    }
}