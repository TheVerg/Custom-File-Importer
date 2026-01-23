@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Data Cleaning Module</h4>
                    <div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="loadTemplates()">
                            <i class="fas fa-folder"></i> Load Template
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Progress Steps -->
                    <div class="steps mb-5">
                        <div class="step-row">
                            <div id="step1" class="step-col active">1. Upload File</div>
                            <div id="step2" class="step-col">2. Configure Cleaning</div>
                            <div id="step3" class="step-col">3. Preview & Apply</div>
                            <div id="step4" class="step-col">4. Download & Import</div>
                        </div>
                    </div>

                    <!-- Step 1: File Upload -->
                    <div id="step1-content" class="step-content">
                        <h5>Upload CSV/Excel File</h5>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="cleaning-file">Select File</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="cleaning-file" accept=".csv,.xlsx,.xls">
                                        <label class="custom-file-label" for="cleaning-file">Choose file</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Supported formats: CSV, XLSX, XLS (Max: 10MB)
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mt-4">
                                    <button class="btn btn-primary" onclick="uploadFileForCleaning()">
                                        <i class="fas fa-upload"></i> Upload & Continue
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div id="file-info" class="mt-3 d-none">
                            <div class="alert alert-info">
                                <h6>File Information</h6>
                                <p><strong>File:</strong> <span id="uploaded-file-name"></span></p>
                                <p><strong>Rows:</strong> <span id="total-rows"></span></p>
                                <p><strong>Preview:</strong></p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead id="file-headers"></thead>
                                        <tbody id="file-sample"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Configure Cleaning -->
                    <div id="step2-content" class="step-content d-none">
                        <h5>Configure Data Cleaning Rules</h5>
                        
                        <!-- Column Selection -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6>Select Columns to Clean</h6>
                                <div id="column-selection" class="d-flex flex-wrap gap-2 mb-3"></div>
                            </div>
                        </div>
                        
                        <!-- Cleaning Rules -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Cleaning Rules</h6>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" onclick="addColumnRule()">
                                            <i class="fas fa-plus"></i> Add Rule to Column
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="cleaning-rules-container"></div>
                                
                                <!-- Transformations -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Column Transformations</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <button class="btn btn-outline-info btn-sm" onclick="addTransformation('split')">
                                                    <i class="fas fa-cut"></i> Split Column
                                                </button>
                                                <button class="btn btn-outline-info btn-sm ml-2" onclick="addTransformation('merge')">
                                                    <i class="fas fa-compress"></i> Merge Columns
                                                </button>
                                            </div>
                                        </div>
                                        <div id="transformations-container" class="mt-3"></div>
                                    </div>
                                </div>
                                
                                <!-- Options -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Options</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="remove-duplicates">
                                                    <label class="form-check-label" for="remove-duplicates">
                                                        Remove Duplicate Rows
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-8" id="duplicate-options" style="display: none;">
                                                <div class="form-group">
                                                    <label>Unique Columns (leave empty for complete row comparison)</label>
                                                    <select class="form-control select2" id="unique-columns" multiple>
                                                        <!-- Options will be populated dynamically -->
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Save Template -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Save Configuration as Template</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Template Name</label>
                                                    <input type="text" class="form-control" id="template-name" placeholder="e.g., Customer Data Cleaning">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <input type="text" class="form-control" id="template-description" placeholder="Optional description">
                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn btn-outline-success btn-sm" onclick="saveTemplate()">
                                            <i class="fas fa-save"></i> Save Template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-secondary" onclick="prevStep(1)">Previous</button>
                            <button class="btn btn-primary" onclick="nextStep(3)">Next: Preview</button>
                        </div>
                    </div>

                    <!-- Step 3: Preview & Apply -->
                    <div id="step3-content" class="step-content d-none">
                        <h5>Preview & Apply Cleaning</h5>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Preview the cleaning results before applying to the entire file.
                                    <button class="btn btn-sm btn-outline-primary float-right" onclick="previewCleaning()">
                                        <i class="fas fa-sync"></i> Refresh Preview
                                    </button>
                                </div>
                                
                                <!-- Preview Tabs -->
                                <ul class="nav nav-tabs" id="previewTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="before-tab" data-toggle="tab" href="#before" role="tab">
                                            Before Cleaning
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="after-tab" data-toggle="tab" href="#after" role="tab">
                                            After Cleaning
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="changes-tab" data-toggle="tab" href="#changes" role="tab">
                                            Changes
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="errors-tab" data-toggle="tab" href="#errors" role="tab">
                                            Validation Errors
                                        </a>
                                    </li>
                                </ul>
                                
                                <div class="tab-content border border-top-0 p-3" id="previewTabContent">
                                    <div class="tab-pane fade show active" id="before" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead id="preview-before-headers"></thead>
                                                <tbody id="preview-before-body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="after" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead id="preview-after-headers"></thead>
                                                <tbody id="preview-after-body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="changes" role="tabpanel">
                                        <div id="changes-list"></div>
                                    </div>
                                    <div class="tab-pane fade" id="errors" role="tabpanel">
                                        <div id="errors-list"></div>
                                    </div>
                                </div>
                                
                                <!-- Apply Cleaning -->
                                <div class="mt-4">
                                    <button class="btn btn-success" onclick="applyCleaning()">
                                        <i class="fas fa-magic"></i> Apply Cleaning to Entire File
                                    </button>
                                    <div class="progress mt-2 d-none" id="cleaning-progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                
                                <!-- Results -->
                                <div id="cleaning-results" class="mt-4 d-none">
                                    <div class="alert alert-success">
                                        <h6>Cleaning Completed Successfully!</h6>
                                        <div id="results-details"></div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary" onclick="downloadCleanedFile()">
                                                <i class="fas fa-download"></i> Download Cleaned File
                                            </button>
                                            <button class="btn btn-outline-primary ml-2" onclick="goToImport()">
                                                <i class="fas fa-database"></i> Import Cleaned File
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-secondary" onclick="prevStep(2)">Previous</button>
                        </div>
                    </div>

                    <!-- Step 4: Download & Import -->
                    <div id="step4-content" class="step-content d-none">
                        <h5>Download & Import Cleaned File</h5>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-success">
                                    <h6>Cleaned File Ready!</h6>
                                    <p>Your data has been cleaned and is ready for download or import.</p>
                                    
                                    <div class="mt-3">
                                        <button class="btn btn-primary" onclick="downloadCleanedFile()">
                                            <i class="fas fa-download"></i> Download Cleaned File
                                        </button>
                                        <button class="btn btn-success ml-2" onclick="goToImportPage()">
                                            <i class="fas fa-database"></i> Go to Import Page
                                        </button>
                                        <button class="btn btn-outline-secondary ml-2" onclick="startNewCleaning()">
                                            <i class="fas fa-redo"></i> Clean Another File
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Cleaning Report -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Cleaning Report</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="cleaning-report-details"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Templates Modal -->
<div class="modal fade" id="templatesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Load Saved Template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="templates-list"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .steps {
        position: relative;
        margin-bottom: 30px;
    }
    .step-row {
        display: flex;
        justify-content: space-between;
        position: relative;
    }
    .step-col {
        text-align: center;
        position: relative;
        flex: 1;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        margin: 0 5px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .step-col.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    .step-content {
        padding: 20px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        background: #f8f9fa;
    }
    
    .column-checkbox {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 8px 12px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    .column-checkbox:hover {
        background: #f8f9fa;
    }
    .column-checkbox.selected {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    
    .cleaning-rule-card {
        border-left: 4px solid #007bff;
        margin-bottom: 15px;
    }
    
    .change-highlight {
        background-color: #fff3cd !important;
    }
    
    .error-highlight {
        background-color: #f8d7da !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
    // Global variables
    let currentStep = 1;
    let uploadedFile = null;
    let cleaningRules = {};
    let transformations = [];
    let availableCleaningRules = {};
    let selectedColumns = [];
    let cleaningResults = null;
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadCleaningRules();
        initEventListeners();
        showStep(1);
        
        // Initialize Select2
        $('#unique-columns').select2({
            placeholder: 'Select columns...',
            allowClear: true
        });
    });
    
    function initEventListeners() {
        // File upload
        document.getElementById('cleaning-file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Choose file';
            document.querySelector('.custom-file-label').textContent = fileName;
        });
        
        // Remove duplicates toggle
        document.getElementById('remove-duplicates').addEventListener('change', function(e) {
            document.getElementById('duplicate-options').style.display = 
                e.target.checked ? 'block' : 'none';
        });
        
        // Tab navigation
        $('#previewTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }
    
    // Step navigation
    function showStep(step) {
        // Hide all steps
        for (let i = 1; i <= 4; i++) {
            document.getElementById(`step${i}`).classList.remove('active');
            document.getElementById(`step${i}-content`).classList.add('d-none');
        }
        
        // Show current step
        document.getElementById(`step${step}`).classList.add('active');
        document.getElementById(`step${step}-content`).classList.remove('d-none');
        currentStep = step;
    }
    
    function nextStep(step) {
        if (validateStep(currentStep)) {
            showStep(step);
            
            // Load specific data for steps
            if (step === 2 && uploadedFile) {
                setupColumnSelection();
                updateAvailableColumns();
            } else if (step === 3) {
                previewCleaning();
            }
        }
    }
    
    function prevStep(step) {
        showStep(step);
    }
    
    function validateStep(step) {
        switch(step) {
            case 1:
                if (!uploadedFile) {
                    alert('Please upload a file first');
                    return false;
                }
                return true;
            case 2:
                // Always valid - cleaning rules are optional
                return true;
            default:
                return true;
        }
    }
    
    // Load available cleaning rules
    async function loadCleaningRules() {
        try {
            const response = await fetch('/data-cleaning/rules');
            const data = await response.json();
            
            if (data.success) {
                availableCleaningRules = data.rules;
                console.log('Cleaning rules loaded:', availableCleaningRules);
            }
        } catch (error) {
            console.error('Error loading cleaning rules:', error);
        }
    }
    
    // Upload file
    async function uploadFileForCleaning() {
        const fileInput = document.getElementById('cleaning-file');
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Please select a file first');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        
        // Show loading
        const originalLabel = document.querySelector('.custom-file-label').textContent;
        document.querySelector('.custom-file-label').textContent = 'Uploading...';
        
        try {
            const response = await fetch('/data-cleaning/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                uploadedFile = data;
                
                // Update file info display
                document.getElementById('uploaded-file-name').textContent = data.file_name;
                document.getElementById('total-rows').textContent = data.total_rows.toLocaleString();
                
                // Display headers
                const headersHtml = data.headers.map(header => 
                    `<th>${header}</th>`
                ).join('');
                document.getElementById('file-headers').innerHTML = `<tr>${headersHtml}</tr>`;
                
                // Display sample data
                let sampleHtml = '';
                data.sample_data.forEach((row, rowIndex) => {
                    sampleHtml += '<tr>';
                    row.forEach(cell => {
                        sampleHtml += `<td>${cell || ''}</td>`;
                    });
                    sampleHtml += '</tr>';
                });
                document.getElementById('file-sample').innerHTML = sampleHtml;
                
                // Show file info
                document.getElementById('file-info').classList.remove('d-none');
                
                // Move to next step
                nextStep(2);
                
            } else {
                alert('Upload failed: ' + data.error);
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert('Upload failed. Please check console for details.');
        } finally {
            document.querySelector('.custom-file-label').textContent = originalLabel;
        }
    }
    
    // Setup column selection
    function setupColumnSelection() {
        const container = document.getElementById('column-selection');
        container.innerHTML = '';
        
        uploadedFile.headers.forEach((header, index) => {
            const columnId = `col_${index}`;
            const checkbox = document.createElement('div');
            checkbox.className = 'column-checkbox';
            checkbox.innerHTML = `
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="${columnId}" 
                           onchange="toggleColumnSelection(${index}, this.checked)">
                    <label class="form-check-label mb-0" for="${columnId}">
                        ${header} (Col ${index + 1})
                    </label>
                </div>
            `;
            container.appendChild(checkbox);
        });
    }
    
    function toggleColumnSelection(columnIndex, isSelected) {
        if (isSelected) {
            selectedColumns.push(columnIndex);
        } else {
            selectedColumns = selectedColumns.filter(idx => idx !== columnIndex);
        }
        
        // Update UI
        const checkboxes = document.querySelectorAll('.column-checkbox');
        checkboxes.forEach((cb, idx) => {
            const checkbox = cb.querySelector('input[type="checkbox"]');
            if (checkbox.checked) {
                cb.classList.add('selected');
            } else {
                cb.classList.remove('selected');
            }
        });
    }
    
    // Update available columns for transformations
    function updateAvailableColumns() {
        const select = document.getElementById('unique-columns');
        select.innerHTML = '';
        
        uploadedFile.headers.forEach((header, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = `${header} (Col ${index + 1})`;
            select.appendChild(option);
        });
        
        // Reinitialize Select2
        $('#unique-columns').trigger('change.select2');
    }
    
    // Add cleaning rule to column
    function addColumnRule() {
        if (selectedColumns.length === 0) {
            alert('Please select at least one column first');
            return;
        }
        
        // For simplicity, add rule to first selected column
        const columnIndex = selectedColumns[0];
        const columnName = uploadedFile.headers[columnIndex];
        
        const ruleId = `rule_${columnIndex}_${Date.now()}`;
        
        const ruleHtml = `
            <div class="card cleaning-rule-card" data-rule-id="${ruleId}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Column</label>
                                <select class="form-control rule-column" 
                                        onchange="updateRuleColumn('${ruleId}', this.value)">
                                    ${uploadedFile.headers.map((header, idx) => 
                                        `<option value="${idx}" ${idx === columnIndex ? 'selected' : ''}>
                                            ${header} (Col ${idx + 1})
                                        </option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Rule Type</label>
                                <select class="form-control rule-type" 
                                        onchange="updateRuleType('${ruleId}', this.value)">
                                    <option value="">Select Rule</option>
                                    ${Object.entries(availableCleaningRules).map(([key, rule]) => 
                                        `<option value="${key}">${rule.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="rule-options-${ruleId}">
                            <!-- Options will be inserted here -->
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm mt-4" 
                                    onclick="removeRule('${ruleId}')">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <small id="rule-desc-${ruleId}" class="text-muted"></small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('cleaning-rules-container').insertAdjacentHTML('beforeend', ruleHtml);
    }
    
    function updateRuleColumn(ruleId, columnIndex) {
        if (!cleaningRules[columnIndex]) {
            cleaningRules[columnIndex] = [];
        }
        // Update rule in cleaningRules object
        // This would need more logic to handle moving rules between columns
    }
    
    function updateRuleType(ruleId, ruleType) {
        const ruleInfo = availableCleaningRules[ruleType];
        const optionsContainer = document.getElementById(`rule-options-${ruleId}`);
        const descElement = document.getElementById(`rule-desc-${ruleId}`);
        
        // Update description
        if (ruleInfo) {
            descElement.textContent = ruleInfo.description;
        }
        
        // Clear and rebuild options
        optionsContainer.innerHTML = '';
        
        if (ruleInfo && ruleInfo.has_options && ruleInfo.options) {
            Object.entries(ruleInfo.options).forEach(([key, option]) => {
                let inputHtml = '';
                
                switch (option.type) {
                    case 'text':
                        inputHtml = `
                            <div class="form-group">
                                <label>${option.label}</label>
                                <input type="text" class="form-control rule-option" 
                                       data-option="${key}" 
                                       placeholder="${option.placeholder || ''}"
                                       ${option.required ? 'required' : ''}>
                            </div>
                        `;
                        break;
                    // Add other input types as needed
                }
                
                optionsContainer.insertAdjacentHTML('beforeend', inputHtml);
            });
        }
    }
    
    function removeRule(ruleId) {
        const ruleElement = document.querySelector(`[data-rule-id="${ruleId}"]`);
        if (ruleElement) {
            ruleElement.remove();
        }
    }
    
    // Add transformation
    function addTransformation(type) {
        const transformationId = `trans_${Date.now()}`;
        
        let html = '';
        if (type === 'split') {
            html = `
                <div class="card mb-3" data-transformation-id="${transformationId}">
                    <div class="card-body">
                        <h6>Split Column</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Column to Split</label>
                                    <select class="form-control split-column">
                                        ${uploadedFile.headers.map((header, idx) => 
                                            `<option value="${idx}">${header} (Col ${idx + 1})</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Delimiter</label>
                                    <input type="text" class="form-control split-delimiter" 
                                           value="," placeholder="e.g., , ; |">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Number of Columns</label>
                                    <input type="number" class="form-control split-count" 
                                           min="2" max="5" value="2">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm mt-4" 
                                        onclick="removeTransformation('${transformationId}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'merge') {
            html = `
                <div class="card mb-3" data-transformation-id="${transformationId}">
                    <div class="card-body">
                        <h6>Merge Columns</h6>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Columns to Merge</label>
                                    <select class="form-control merge-columns" multiple>
                                        ${uploadedFile.headers.map((header, idx) => 
                                            `<option value="${idx}">${header} (Col ${idx + 1})</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Separator</label>
                                    <input type="text" class="form-control merge-separator" 
                                           value=" " placeholder="e.g., space, comma">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>New Column Name</label>
                                    <input type="text" class="form-control merge-name" 
                                           placeholder="e.g., Full Name">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="removeTransformation('${transformationId}')">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        document.getElementById('transformations-container').insertAdjacentHTML('beforeend', html);
        
        // Initialize Select2 for merge columns
        if (type === 'merge') {
            $(`[data-transformation-id="${transformationId}"] .merge-columns`).select2({
                placeholder: 'Select columns...'
            });
        }
    }
    
    function removeTransformation(transformationId) {
        const element = document.querySelector(`[data-transformation-id="${transformationId}"]`);
        if (element) {
            element.remove();
        }
    }
    
    // Preview cleaning
    async function previewCleaning() {
        // Collect all rules
        const rules = collectRules();
        const trans = collectTransformations();
        const options = collectOptions();
        
        try {
            const response = await fetch('/data-cleaning/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    file_path: uploadedFile.file_path,
                    file_type: uploadedFile.file_type,
                    cleaning_rules: rules,
                    transformations: trans,
                    options: options
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                displayPreview(data);
            } else {
                alert('Preview failed: ' + data.error);
            }
        } catch (error) {
            console.error('Preview error:', error);
            alert('Preview failed. Please check console for details.');
        }
    }
    
    function collectRules() {
        // Implementation to collect rules from UI
        return cleaningRules; // This should be populated from UI
    }
    
    function collectTransformations() {
        // Implementation to collect transformations from UI
        return transformations; // This should be populated from UI
    }
    
    function collectOptions() {
        const removeDupes = document.getElementById('remove-duplicates').checked;
        const uniqueCols = $('#unique-columns').val() || [];
        
        return {
            remove_duplicates: removeDupes,
            unique_columns: uniqueCols.map(col => parseInt(col))
        };
    }
    
    function displayPreview(data) {
        // Display before/after comparison
        // This would be a comprehensive comparison view
        console.log('Preview data:', data);
        
        // Example: Show sample of changes
        if (data.cleaned_sample && data.original_sample) {
            // Update the preview tabs with data
        }
    }
    
    // Apply cleaning
    async function applyCleaning() {
        const rules = collectRules();
        const trans = collectTransformations();
        const options = collectOptions();
        
        // Show progress
        const progressBar = document.querySelector('#cleaning-progress .progress-bar');
        document.getElementById('cleaning-progress').classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        
        try {
            const response = await fetch('/data-cleaning/apply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    file_path: uploadedFile.file_path,
                    file_name: uploadedFile.file_name,
                    file_type: uploadedFile.file_type,
                    cleaning_rules: rules,
                    transformations: trans,
                    options: options,
                    headers: uploadedFile.headers
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                cleaningResults = data;
                
                // Update progress
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
                
                // Show results
                displayCleaningResults(data);
                
                // Move to next step
                setTimeout(() => {
                    nextStep(4);
                }, 1000);
                
            } else {
                alert('Cleaning failed: ' + data.error);
            }
        } catch (error) {
            console.error('Apply cleaning error:', error);
            alert('Cleaning failed. Please check console for details.');
        } finally {
            document.getElementById('cleaning-progress').classList.add('d-none');
        }
    }
    
    function displayCleaningResults(data) {
        const resultsDiv = document.getElementById('cleaning-results');
        const detailsDiv = document.getElementById('results-details');
        
        detailsDiv.innerHTML = `
            <p><strong>Original Rows:</strong> ${data.total_rows.toLocaleString()}</p>
            <p><strong>Cleaned Rows:</strong> ${data.cleaned_rows.toLocaleString()}</p>
            <p><strong>Validation Errors:</strong> ${data.validation_errors.toLocaleString()}</p>
            <p><strong>Cleaned File:</strong> ${data.cleaned_file_name}</p>
        `;
        
        resultsDiv.classList.remove('d-none');
        
        // Also display in step 4
        if (data.cleaning_report) {
            const reportDiv = document.getElementById('cleaning-report-details');
            reportDiv.innerHTML = `
                <p><strong>Total Rows Processed:</strong> ${data.cleaning_report.total_rows}</p>
                <p><strong>Rows with Validation Errors:</strong> ${data.cleaning_report.validation_errors}</p>
                ${data.cleaning_report.sample_changes && data.cleaning_report.sample_changes.length > 0 ? 
                    `<p><strong>Sample Changes:</strong></p>
                     <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Column</th>
                                    <th>Original</th>
                                    <th>Cleaned</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.cleaning_report.sample_changes.map(change => 
                                    Object.entries(change.changes).map(([col, values]) => `
                                        <tr>
                                            <td>${change.row}</td>
                                            <td>Column ${parseInt(col) + 1}</td>
                                            <td>${values.original}</td>
                                            <td>${values.cleaned}</td>
                                        </tr>
                                    `).join('')
                                ).join('')}
                            </tbody>
                        </table>
                     </div>` : ''
                }
            `;
        }
    }
    
    // Download cleaned file
    async function downloadCleanedFile() {
        if (!cleaningResults) {
            alert('No cleaned file available. Please apply cleaning first.');
            return;
        }
        
        window.location.href = `/data-cleaning/download/${cleaningResults.cleaned_file_name}`;
    }
    
    // Go to import page
    function goToImportPage() {
        window.location.href = '/import';
    }
    
    // Start new cleaning
    function startNewCleaning() {
        // Reset everything
        uploadedFile = null;
        cleaningRules = {};
        transformations = [];
        selectedColumns = [];
        cleaningResults = null;
        
        // Reset UI
        document.getElementById('file-info').classList.add('d-none');
        document.getElementById('cleaning-rules-container').innerHTML = '';
        document.getElementById('transformations-container').innerHTML = '';
        document.getElementById('cleaning-results').classList.add('d-none');
        document.getElementById('cleaning-file').value = '';
        document.querySelector('.custom-file-label').textContent = 'Choose file';
        
        // Go back to step 1
        showStep(1);
    }
    
    // Template management
    async function loadTemplates() {
        try {
            const response = await fetch('/data-cleaning/templates');
            const data = await response.json();
            
            if (data.success) {
                displayTemplates(data.templates);
            }
        } catch (error) {
            console.error('Load templates error:', error);
        }
    }
    
    function displayTemplates(templates) {
        const listDiv = document.getElementById('templates-list');
        
        if (templates.length === 0) {
            listDiv.innerHTML = '<p class="text-muted">No templates saved yet.</p>';
        } else {
            listDiv.innerHTML = templates.map(template => `
                <div class="card mb-2">
                    <div class="card-body">
                        <h6>${template.name}</h6>
                        <p class="text-muted small">${template.description || 'No description'}</p>
                        <p class="small">Created: ${template.created_at}</p>
                        <button class="btn btn-sm btn-primary" 
                                onclick="loadTemplate('${template.file_name}')">
                            Load Template
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        $('#templatesModal').modal('show');
    }
    
    async function loadTemplate(templateName) {
        try {
            const response = await fetch(`/data-cleaning/template/${templateName}`);
            const data = await response.json();
            
            if (data.success) {
                // Apply template to current configuration
                applyTemplate(data.template);
                $('#templatesModal').modal('hide');
            }
        } catch (error) {
            console.error('Load template error:', error);
        }
    }
    
    function applyTemplate(template) {
        // Apply template configuration to current UI
        // This would populate cleaning rules, transformations, and options
        console.log('Applying template:', template);
        
        // For now, just show a message
        alert(`Template "${template.name}" loaded. Configuration would be applied here.`);
    }
    
    async function saveTemplate() {
        const name = document.getElementById('template-name').value.trim();
        const description = document.getElementById('template-description').value.trim();
        
        if (!name) {
            alert('Please enter a template name');
            return;
        }
        
        const rules = collectRules();
        const trans = collectTransformations();
        const options = collectOptions();
        
        try {
            const response = await fetch('/data-cleaning/save-template', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    name: name,
                    description: description,
                    cleaning_rules: rules,
                    transformations: trans,
                    options: options
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Template saved successfully!');
            } else {
                alert('Save failed: ' + data.error);
            }
        } catch (error) {
            console.error('Save template error:', error);
            alert('Save failed. Please check console for details.');
        }
    }
</script>
@endpush