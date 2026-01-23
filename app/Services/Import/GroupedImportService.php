<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Log;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class GroupedImportService
{
    /**
     * Parse a CSV file with grouping headers and extract loan type information
     */
    public function parseGroupedFile(string $filePath, string $fileType): array
    {
        // Increase execution time limit for large files
        set_time_limit(300); // 5 minutes
        
        Log::info('Parsing grouped file', [
            'file_path' => $filePath,
            'file_type' => $fileType
        ]);

        if ($fileType === 'csv') {
            $reader = ReaderEntityFactory::createCSVReader();
            $reader->setFieldDelimiter(',');
            $reader->setFieldEnclosure('"');
        } else {
            $reader = ReaderEntityFactory::createXLSXReader();
        }
        
        $reader->open($filePath);
        
        $groups = [];
        $currentGroup = null;
        $headers = null;
        $rowIndex = 0;
        $emptyRowCount = 0;
        $maxEmptyRows = 50; // Skip processing after 50 consecutive empty rows
        
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $GLOBALS['rowIndex'] = $rowIndex; // For debug logging reference
                $cells = $row->getCells();
                $firstCell = $cells[0]?->getValue();
                
                // Check if row is completely empty (optimization)
                $isEmptyRow = true;
                foreach ($cells as $cell) {
                    if (!empty(trim($cell?->getValue() ?? ''))) {
                        $isEmptyRow = false;
                        break;
                    }
                }
                
                if ($isEmptyRow) {
                    $emptyRowCount++;
                    if ($emptyRowCount > $maxEmptyRows) {
                        Log::info('Skipping remaining rows after ' . $maxEmptyRows . ' consecutive empty rows');
                        break;
                    }
                    continue;
                } else {
                    $emptyRowCount = 0; // Reset counter when we find non-empty row
                }
                
                // Log first few rows for debugging (reduced logging)
                if ($rowIndex <= 5) {
                    Log::debug('Row ' . $rowIndex, [
                        'first_cell' => $firstCell,
                        'cell_count' => count($cells)
                    ]);
                }
                
                // IMPORTANT: Check for grouping header FIRST, before checking for regular headers
                // Check if this is a grouping header row
               $groupHeader = $this->isGroupingHeaderRow($cells);

                if ($groupHeader) {
                    if ($currentGroup && !empty($currentGroup['data'])) {
                        $groups[] = $currentGroup;
                    }

                    $loanTypeInfo = $this->parseLoanTypeHeader($groupHeader);

                    $currentGroup = [
                        'loan_type_code' => $loanTypeInfo['code'],
                        'loan_type_name' => $loanTypeInfo['name'],
                        'loan_type_full' => $loanTypeInfo['full'],
                        'data' => [],
                        'headers' => null
                    ];

                    Log::info('Found loan type group', $loanTypeInfo);
                    continue;
                }

                
                // Check if this is a header row (contains column names)
                if ($this->isHeaderRow($cells)) {
                    $headers = array_map('trim', array_map(function($cell) {
                        return $cell->getValue();
                    }, $cells));
                    
                    if ($currentGroup) {
                        $currentGroup['headers'] = $headers;
                    }
                    
                    Log::info('Found headers', ['headers' => $headers]);
                    continue;
                }
                
                // Process data rows
                if ($headers && $currentGroup) {
                    $rowData = [];
                    foreach ($cells as $index => $cell) {
                        $header = $headers[$index] ?? $index;
                        $rowData[$header] = $cell->getValue();
                    }
                    
                    // Add loan type info to each row
                    $rowData['loan_type_code'] = $currentGroup['loan_type_code'];
                    $rowData['loan_type_name'] = $currentGroup['loan_type_name'];
                    $rowData['loan_type_full'] = $currentGroup['loan_type_full'];
                    
                    $currentGroup['data'][] = $rowData;
                }
            }
            
            // Add the last group
            if ($currentGroup && !empty($currentGroup['data'])) {
                $groups[] = $currentGroup;
            }
            
            break; // Only process first sheet
        }
        
        $reader->close();
        
        Log::info('Grouped file parsing completed', [
            'groups_found' => count($groups),
            'total_rows' => array_sum(array_map(fn($g) => count($g['data']), $groups))
        ]);
        
        return $groups;
    }
    
    /**
     * Check if a row contains a grouping header
     */
        private function isGroupingHeaderRow(array $cells): ?string
        {
            foreach ($cells as $cell) {
                $value = trim((string)$cell->getValue());

                if (preg_match('/Loan\s*Type\s*:?\s*\d+/i', $value)) {
                    return $value;
                }
            }

            // Only log debug for first 10 rows to reduce log spam
            if (($GLOBALS['rowIndex'] ?? 0) <= 10) {
                Log::debug('No grouping header found in row', [
                    'cells' => array_map(fn($cell) => $cell?->getValue(), $cells)
                ]);
            }

            return null;
        }

    
    /**
     * Parse loan type information from grouping header
     */
    private function parseLoanTypeHeader(string $header): array
    {
        $header = trim($header);
        
        // Extract code and name using regex
        // Handle both "Loan Type:" and "Loan Type :" (with space before colon)
        if (preg_match('/Loan\s*Type\s*:\s*(\d+)-(.+)/i', $header, $matches)) {
            return [
                'code' => trim($matches[1]),
                'name' => trim($matches[2]),
                'full' => $header
            ];
        }
        
        // Fallback if regex doesn't match
        return [
            'code' => 'UNKNOWN',
            'name' => $header,
            'full' => $header
        ];
    }
    
    /**
     * Check if a row is a header row (contains typical column names)
     */
    private function isHeaderRow(array $cells): bool
    {
        if (empty($cells)) {
            return false;
        }
        
        $commonHeaders = [
            'customer', 'name', 'value', 'maturity', 'tenure', 'interest', 
            'approved', 'disbursed', 'principal', 'collateral', 'repayment',
            'total', 'arrears', 'days', 'sr', 'a/c', 'national'
        ];
        
        $firstFewCells = array_slice($cells, 0, 5);
        $headerCount = 0;
        
        foreach ($firstFewCells as $cell) {
            $value = strtolower(trim($cell?->getValue() ?? ''));
            
            // Skip empty cells or cells that look like grouping headers
            if (empty($value) || str_contains($value, 'loan type:')) {
                continue;
            }
            
            foreach ($commonHeaders as $header) {
                if (str_contains($value, $header)) {
                    $headerCount++;
                    break;
                }
            }
        }
        
        // Consider it a header if at least 2 cells contain common header terms
        // AND it doesn't look like a grouping header
        return $headerCount >= 2;
    }
    
    /**
     * Get available loan types from the grouped file
     */
    public function getLoanTypes(string $filePath, string $fileType): array
    {
        $groups = $this->parseGroupedFile($filePath, $fileType);
        
        return array_map(function($group) {
            return [
                'code' => $group['loan_type_code'],
                'name' => $group['loan_type_name'],
                'full' => $group['loan_type_full'],
                'row_count' => count($group['data'])
            ];
        }, $groups);
    }
    
    /**
     * Get sample data for a specific loan type
     */
    public function getLoanTypeSample(string $filePath, string $fileType, string $loanTypeCode, int $sampleSize = 5): array
    {
        $groups = $this->parseGroupedFile($filePath, $fileType);
        
        foreach ($groups as $group) {
            if ($group['loan_type_code'] === $loanTypeCode) {
                return array_slice($group['data'], 0, $sampleSize);
            }
        }
        
        return [];
    }
    
    /**
     * Get columns available for a specific loan type
     */
    public function getLoanTypeColumns(string $filePath, string $fileType, string $loanTypeCode): array
    {
        $groups = $this->parseGroupedFile($filePath, $fileType);
        
        foreach ($groups as $group) {
            if ($group['loan_type_code'] === $loanTypeCode) {
                // Return only the actual data headers, not loan type metadata
                return $group['headers'] ?? [];
            }
        }
        
        return [];
    }
}
