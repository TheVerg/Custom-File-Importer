<?php

namespace App\Services\Import;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DataCleaningService
{
    /**
     * Available cleaning rules
     */
    public function getAvailableRules(): array
    {
        return [
            'trim' => [
                'name' => 'Trim Whitespace',
                'description' => 'Remove whitespace from beginning and end of values',
                'has_options' => false,
            ],
            'uppercase' => [
                'name' => 'Convert to Uppercase',
                'description' => 'Convert all characters to uppercase',
                'has_options' => false,
            ],
            'lowercase' => [
                'name' => 'Convert to Lowercase',
                'description' => 'Convert all characters to lowercase',
                'has_options' => false,
            ],
            'title_case' => [
                'name' => 'Convert to Title Case',
                'description' => 'Convert to proper title case (first letter of each word capitalized)',
                'has_options' => false,
            ],
            'remove_special_chars' => [
                'name' => 'Remove Special Characters',
                'description' => 'Remove non-alphanumeric characters',
                'has_options' => true,
                'options' => [
                    'allowed_chars' => [
                        'type' => 'text',
                        'label' => 'Allowed Characters (optional)',
                        'placeholder' => 'e.g., -_.,()',
                    ],
                ],
            ],
            'replace' => [
                'name' => 'Find and Replace',
                'description' => 'Replace specific text with another',
                'has_options' => true,
                'options' => [
                    'find' => [
                        'type' => 'text',
                        'label' => 'Find',
                        'required' => true,
                    ],
                    'replace' => [
                        'type' => 'text',
                        'label' => 'Replace With',
                        'required' => true,
                    ],
                    'case_sensitive' => [
                        'type' => 'checkbox',
                        'label' => 'Case Sensitive',
                        'default' => false,
                    ],
                ],
            ],
            'format_date' => [
                'name' => 'Format Date',
                'description' => 'Convert date from one format to another',
                'has_options' => true,
                'options' => [
                    'input_format' => [
                        'type' => 'text',
                        'label' => 'Input Format',
                        'placeholder' => 'e.g., d/m/Y, Y-m-d, m/d/Y',
                        'required' => true,
                    ],
                    'output_format' => [
                        'type' => 'text',
                        'label' => 'Output Format',
                        'placeholder' => 'e.g., Y-m-d, d/m/Y',
                        'required' => true,
                    ],
                ],
            ],
            'format_number' => [
                'name' => 'Format Number',
                'description' => 'Format numeric values',
                'has_options' => true,
                'options' => [
                    'decimal_places' => [
                        'type' => 'number',
                        'label' => 'Decimal Places',
                        'min' => 0,
                        'max' => 10,
                        'default' => 2,
                    ],
                    'thousands_separator' => [
                        'type' => 'checkbox',
                        'label' => 'Use Thousands Separator',
                        'default' => true,
                    ],
                ],
            ],
            'extract_email' => [
                'name' => 'Extract Email',
                'description' => 'Extract email addresses from text',
                'has_options' => false,
            ],
            'extract_phone' => [
                'name' => 'Extract Phone Number',
                'description' => 'Extract and format phone numbers',
                'has_options' => true,
                'options' => [
                    'format' => [
                        'type' => 'select',
                        'label' => 'Output Format',
                        'options' => [
                            '(XXX) XXX-XXXX' => 'US Format',
                            'XXX-XXX-XXXX' => 'US Simple',
                            'XXXXX-XXXXX' => 'International',
                        ],
                    ],
                ],
            ],
            'remove_duplicates' => [
                'name' => 'Remove Duplicates',
                'description' => 'Remove duplicate values in this column',
                'has_options' => false,
            ],
            'default_value' => [
                'name' => 'Set Default Value',
                'description' => 'Set a default value for empty cells',
                'has_options' => true,
                'options' => [
                    'default' => [
                        'type' => 'text',
                        'label' => 'Default Value',
                        'required' => true,
                    ],
                ],
            ],
            'validate_email' => [
                'name' => 'Validate Email',
                'description' => 'Validate email format and mark invalid',
                'has_options' => false,
            ],
            'validate_required' => [
                'name' => 'Validate Required',
                'description' => 'Mark empty values as invalid',
                'has_options' => false,
            ],
            'split_column' => [
                'name' => 'Split Column',
                'description' => 'Split column into multiple columns',
                'has_options' => true,
                'options' => [
                    'delimiter' => [
                        'type' => 'text',
                        'label' => 'Delimiter',
                        'placeholder' => 'e.g., , ; | space',
                        'required' => true,
                    ],
                    'new_columns' => [
                        'type' => 'number',
                        'label' => 'Number of New Columns',
                        'min' => 1,
                        'max' => 5,
                        'default' => 2,
                    ],
                    'column_names' => [
                        'type' => 'text',
                        'label' => 'New Column Names (comma-separated)',
                        'placeholder' => 'e.g., first_name,last_name',
                    ],
                ],
            ],
            'merge_columns' => [
                'name' => 'Merge Columns',
                'description' => 'Merge multiple columns into one',
                'has_options' => true,
                'options' => [
                    'separator' => [
                        'type' => 'text',
                        'label' => 'Separator',
                        'placeholder' => 'e.g., space, comma, dash',
                        'default' => ' ',
                    ],
                ],
            ],
        ];
    }

    /**
     * Apply cleaning rules to data
     */
    public function cleanData(array $data, array $cleaningRules, array &$validationErrors = []): array
    {
        $cleanedData = [];
        $rowIndex = 0;

        foreach ($data as $row) {
            $rowIndex++;
            $cleanedRow = $row;
            $rowErrors = [];

            foreach ($cleaningRules as $columnIndex => $rules) {
                if (!isset($row[$columnIndex])) {
                    continue;
                }

                $value = $row[$columnIndex];
                $originalValue = $value;

                foreach ($rules as $rule) {
                    $value = $this->applyRule($value, $rule, $rowIndex, $columnIndex, $rowErrors);
                }

                $cleanedRow[$columnIndex] = $value;
                
                // Log if value was changed
                if ($originalValue !== $value) {
                    Log::debug('Value cleaned', [
                        'row' => $rowIndex,
                        'column' => $columnIndex,
                        'original' => $originalValue,
                        'cleaned' => $value,
                    ]);
                }
            }

            $cleanedData[] = $cleanedRow;
            
            if (!empty($rowErrors)) {
                $validationErrors[$rowIndex] = $rowErrors;
            }
        }

        return $cleanedData;
    }

    /**
     * Apply a single cleaning rule to a value
     */
    private function applyRule($value, array $rule, int $rowIndex, int $colIndex, array &$errors)
    {
        $ruleType = $rule['rule'];
        $options = $rule['options'] ?? [];

        try {
            switch ($ruleType) {
                case 'trim':
                    return is_string($value) ? trim($value) : $value;

                case 'uppercase':
                    return is_string($value) ? mb_strtoupper($value, 'UTF-8') : $value;

                case 'lowercase':
                    return is_string($value) ? mb_strtolower($value, 'UTF-8') : $value;

                case 'title_case':
                    return is_string($value) ? mb_convert_case($value, MB_CASE_TITLE, 'UTF-8') : $value;

                case 'remove_special_chars':
                    if (!is_string($value)) return $value;
                    
                    $allowed = $options['allowed_chars'] ?? '';
                    $pattern = $allowed ? "/[^a-zA-Z0-9" . preg_quote($allowed, '/') . "]/u" : "/[^a-zA-Z0-9\s]/u";
                    return preg_replace($pattern, '', $value);

                case 'replace':
                    if (!is_string($value)) return $value;
                    
                    $find = $options['find'] ?? '';
                    $replace = $options['replace'] ?? '';
                    $caseSensitive = $options['case_sensitive'] ?? false;
                    
                    if ($caseSensitive) {
                        return str_replace($find, $replace, $value);
                    } else {
                        return str_ireplace($find, $replace, $value);
                    }

                case 'format_date':
                    if (empty($value)) return $value;
                    
                    $inputFormat = $options['input_format'] ?? 'Y-m-d';
                    $outputFormat = $options['output_format'] ?? 'Y-m-d';
                    
                    try {
                        $date = \DateTime::createFromFormat($inputFormat, $value);
                        if ($date) {
                            return $date->format($outputFormat);
                        }
                        
                        // Try common formats as fallback
                        $timestamp = strtotime($value);
                        if ($timestamp !== false) {
                            return date($outputFormat, $timestamp);
                        }
                        
                        $errors[] = "Invalid date format: {$value}";
                        return $value;
                    } catch (\Exception $e) {
                        $errors[] = "Date parsing error: {$e->getMessage()}";
                        return $value;
                    }

                case 'format_number':
                    if (!is_numeric($value)) return $value;
                    
                    $decimalPlaces = $options['decimal_places'] ?? 2;
                    $useThousands = $options['thousands_separator'] ?? true;
                    
                    return number_format((float)$value, $decimalPlaces, '.', $useThousands ? ',' : '');

                case 'extract_email':
                    if (!is_string($value)) return $value;
                    
                    preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $value, $matches);
                    return $matches[0] ?? $value;

                case 'extract_phone':
                    if (!is_string($value)) return $value;
                    
                    // Remove all non-numeric characters
                    $digits = preg_replace('/\D/', '', $value);
                    
                    if (strlen($digits) === 10) {
                        $format = $options['format'] ?? '(XXX) XXX-XXXX';
                        return preg_replace('/(\d{3})(\d{3})(\d{4})/', $format, $digits);
                    }
                    
                    return $value;

                case 'remove_duplicates':
                    // This is handled at the data level, not row level
                    return $value;

                case 'default_value':
                    if (empty($value) && $value !== '0' && $value !== 0) {
                        return $options['default'] ?? '';
                    }
                    return $value;

                case 'validate_email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Invalid email format: {$value}";
                    }
                    return $value;

                case 'validate_required':
                    if (empty($value) && $value !== '0' && $value !== 0) {
                        $errors[] = "Required field is empty";
                    }
                    return $value;

                case 'split_column':
                    // This creates new columns, handled separately
                    return $value;

                case 'merge_columns':
                    // This merges multiple columns, handled separately
                    return $value;

                default:
                    return $value;
            }
        } catch (\Exception $e) {
            Log::error('Error applying cleaning rule', [
                'rule' => $ruleType,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            
            $errors[] = "Error applying rule '{$ruleType}': {$e->getMessage()}";
            return $value;
        }
    }

    /**
     * Apply column transformations (split, merge)
     */
    public function applyColumnTransformations(array $data, array $transformations): array
    {
        foreach ($transformations as $transformation) {
            $type = $transformation['type'];
            
            switch ($type) {
                case 'split':
                    $data = $this->applySplitTransformation($data, $transformation);
                    break;
                    
                case 'merge':
                    $data = $this->applyMergeTransformation($data, $transformation);
                    break;
            }
        }
        
        return $data;
    }

    private function applySplitTransformation(array $data, array $transformation): array
    {
        $columnIndex = $transformation['column_index'];
        $delimiter = $transformation['delimiter'] ?? ',';
        $numColumns = $transformation['new_columns'] ?? 2;
        $columnNames = $transformation['column_names'] ?? [];
        
        if ($delimiter === 'space') {
            $delimiter = ' ';
        } elseif ($delimiter === 'tab') {
            $delimiter = "\t";
        }
        
        $processedData = [];
        
        foreach ($data as $row) {
            $newRow = $row;
            
            if (isset($newRow[$columnIndex])) {
                $parts = explode($delimiter, $newRow[$columnIndex], $numColumns);
                $parts = array_pad($parts, $numColumns, '');
                
                // Remove the original column
                array_splice($newRow, $columnIndex, 1);
                
                // Insert new columns
                array_splice($newRow, $columnIndex, 0, $parts);
            }
            
            $processedData[] = $newRow;
        }
        
        return $processedData;
    }

    private function applyMergeTransformation(array $data, array $transformation): array
    {
        $columnIndices = $transformation['column_indices'] ?? [];
        $separator = $transformation['separator'] ?? ' ';
        $newColumnName = $transformation['new_column_name'] ?? 'merged';
        
        if (empty($columnIndices)) {
            return $data;
        }
        
        $processedData = [];
        
        foreach ($data as $row) {
            $newRow = $row;
            
            // Collect values to merge
            $values = [];
            foreach ($columnIndices as $index) {
                if (isset($row[$index])) {
                    $values[] = $row[$index];
                }
            }
            
            // Create merged value
            $mergedValue = implode($separator, $values);
            
            // Remove original columns
            rsort($columnIndices); // Remove from highest index first
            foreach ($columnIndices as $index) {
                array_splice($newRow, $index, 1);
            }
            
            // Add merged column at the position of the first removed column
            $insertPosition = min($columnIndices);
            array_splice($newRow, $insertPosition, 0, [$mergedValue]);
            
            $processedData[] = $newRow;
        }
        
        return $processedData;
    }

    /**
     * Remove duplicate rows based on specific columns
     */
    public function removeDuplicates(array $data, array $uniqueColumns = []): array
    {
        if (empty($uniqueColumns)) {
            // Remove completely identical rows
            return array_values(array_unique($data, SORT_REGULAR));
        }
        
        $seen = [];
        $result = [];
        
        foreach ($data as $row) {
            $key = '';
            foreach ($uniqueColumns as $colIndex) {
                if (isset($row[$colIndex])) {
                    $key .= $row[$colIndex] . '|';
                }
            }
            
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $row;
            }
        }
        
        return $result;
    }

    /**
     * Generate cleaning report
     */
    public function generateReport(array $originalData, array $cleanedData, array $validationErrors): array
    {
        $report = [
            'total_rows' => count($originalData),
            'cleaned_rows' => count($cleanedData),
            'validation_errors' => count($validationErrors),
            'error_details' => $validationErrors,
            'sample_changes' => [],
        ];
        
        // Find sample changes
        $sampleSize = min(5, count($originalData));
        for ($i = 0; $i < $sampleSize; $i++) {
            if (isset($originalData[$i]) && isset($cleanedData[$i])) {
                $changes = [];
                foreach ($originalData[$i] as $colIndex => $value) {
                    if (isset($cleanedData[$i][$colIndex]) && $originalData[$i][$colIndex] !== $cleanedData[$i][$colIndex]) {
                        $changes[$colIndex] = [
                            'original' => $originalData[$i][$colIndex],
                            'cleaned' => $cleanedData[$i][$colIndex],
                        ];
                    }
                }
                
                if (!empty($changes)) {
                    $report['sample_changes'][] = [
                        'row' => $i + 1,
                        'changes' => $changes,
                    ];
                }
            }
        }
        
        return $report;
    }
}